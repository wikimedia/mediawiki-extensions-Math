<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2015 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

use MediaWiki\Logger\LoggerFactory;
use ValueFormatters\FormatterOptions;
use ValueFormatters\StringFormatter;
use ValueParsers\StringParser;
use Wikibase\Lib\EscapingValueFormatter;
use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;
use Wikibase\Repo\WikibaseRepo;


class MathHooks {
	const MATHCACHEKEY = 'math=';

	public static function mathConstantToString( $value, array $defs, $prefix, $default ) {
		foreach ( $defs as $defKey => $defValue ) {
			if ( !defined( $defKey ) ) {
				define( $defKey, $defValue );
			} elseif ( $defValue !== constant( $defKey ) ) {
				throw new Exception( 'Math constant "'. $defKey . '" has unexpected value "' .
					constant( $defKey ) . '" instead of "' . $defValue );
			}
		}
		$invDefs = array_flip( $defs );
		if ( is_int( $value ) ) {
			if ( array_key_exists( $value, $invDefs ) ) {
				$value = $invDefs[$value];
			} else {
				return $default;
			}
		}
		if ( is_string( $value ) ) {
			$newValues = array();
			foreach ( $defs as $k => $v ) {
				$newValues[$k] = preg_replace_callback( '/_(.)/', function ( $matches ) {
					return strtoupper( $matches[1] );
				}, strtolower( substr( $k, strlen( $prefix ) ) ) );
			}
			if ( array_key_exists( $value, $defs ) ) {
				return $newValues[$value];
			} elseif ( in_array( $value, $newValues ) ) {
				return $value;
			}
		}
		return $default;
	}

	public static function mathStyleToString( $style, $default = 'inlineDisplaystyle' ) {
		$defs = array(
			'MW_MATHSTYLE_INLINE_DISPLAYSTYLE'  => 0, // default large operator inline
			'MW_MATHSTYLE_DISPLAY'              => 1, // large operators centered in a new line
			'MW_MATHSTYLE_INLINE'               => 2, // small operators inline
			'MW_MATHSTYLE_LINEBREAK'            => 3, // break long lines (experimental)
		);
		return self::mathConstantToString( $style, $defs, $prefix = 'MW_MATHSTYLE_', $default );
	}

	public static function mathCheckToString( $style, $default = 'always' ) {
		$defs = array(
			'MW_MATH_CHECK_ALWAYS' => 0,
			'MW_MATH_CHECK_NEVER'  => 1,
			'MW_MATH_CHECK_NEW'    => 2,
		);
		return self::mathConstantToString( $style, $defs, $prefix = 'MW_MATH_CHECK_', $default );
	}

	public static function mathModeToString( $mode, $default = 'png' ) {
		// The following deprecated modes have been removed:
		// 'MW_MATH_SIMPLE'      => 1
		// 'MW_MATH_HTML'        => 2
		// 'MW_MATH_MODERN'      => 4
		// 'MW_MATH_MATHJAX'     => 6
		// 'MW_MATH_LATEXML_JAX' => 8

		$defs = array(
			'MW_MATH_PNG'    => 0,
			'MW_MATH_SOURCE' => 3,
			'MW_MATH_MATHML' => 5,
			'MW_MATH_LATEXML'=> 7 );

		return self::mathConstantToString( $mode, $defs, $prefix = 'MW_MATH_', $default );
	}

	public static function mathModeToHashKey( $mode, $default = 0 ) {
		$defs = array(
			'png'    => 0,
			'source' => 3,
			'mathml' => 5,
			'latexml'=> 7 );

		if ( array_key_exists( $mode, $defs ) ) {
			return $defs[$mode];
		} else {
			return $default;
		}
	}

	/*
	 * Generate a user dependent hash cache key.
	 * The hash key depends on the rendering mode.
	 * @param &$confstr The to-be-hashed key string that is being constructed
	 * @param User $user reference to the current user
	 * @param array &$forOptions userOptions used on that page
	 */
	public static function onPageRenderingHash( &$confstr, $user = false, &$forOptions = array() ) {
		global $wgUser;

		// To be independent of the MediaWiki core version,
		// we check if the core caching logic for math is still available.
		if ( !is_callable( 'ParserOptions::getMath' ) && in_array( 'math', $forOptions ) ) {
			if ( $user === false ) {
				$user = $wgUser;
			}

			$mathString = self::mathModeToString( $user->getOption( 'math' ) );
			$mathOption = self::mathModeToHashKey( $mathString, 0 );
			// Check if the key already contains the math option part
			if (
				!preg_match(
					'/(^|!)' . self::MATHCACHEKEY . $mathOption . '(!|$)/',
					$confstr
				)
			) {
				// The math part of cache key starts with "math="
				// followed by a star or a number for the math mode
				if ( preg_match( '/(^|!)' . self::MATHCACHEKEY . '[*\d]m?(!|$)/', $confstr ) ) {
					$confstr = preg_replace(
						'/(^|!)' . self::MATHCACHEKEY . '[*\d]m?(!|$)/',
						'\1' . self::MATHCACHEKEY . $mathOption . '\2',
						$confstr
					);
				} else {
					$confstr .= '!' . self::MATHCACHEKEY . $mathOption;
				}

				LoggerFactory::getInstance( 'Math' )->debug( "New cache key: $confstr" );
			} else {
				LoggerFactory::getInstance( 'Math' )->debug( "Cache key found: $confstr" );
			}
		}

		return true;
	}

	/**
	 * Set up $wgMathPath and $wgMathDirectory globals if they're not already set.
	 */
	static function setup() {
		global $wgMathPath, $wgMathDirectory,
			$wgUploadPath, $wgUploadDirectory;

		if ( $wgMathPath === false ) {
			$wgMathPath = "{$wgUploadPath}/math";
		}

		if ( $wgMathDirectory === false ) {
			$wgMathDirectory = "{$wgUploadDirectory}/math";
		}
	}

	/**
	 * Register the <math> tag with the Parser.
	 *
	 * @param $parser Parser instance of Parser
	 * @return Boolean: true
	 */
	static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'math', array( 'MathHooks', 'mathTagHook' ) );

		return true;
	}

	/**
	 * Callback function for the <math> parser hook.
	 *
	 * @param $content (the LaTeX input)
	 * @param $attributes
	 * @param Parser $parser
	 * @return array
	 */
	static function mathTagHook( $content, $attributes, $parser ) {

		if ( trim( $content ) === '' ) { // bug 8372
			return '';
		}

		$mode = self::mathModeToString( $parser->getUser()->getOption( 'math' ) );
		// Indicate that this page uses math.
		// This affects the page caching behavior.
		if ( is_callable( 'ParserOptions::getMath' ) ) {
			$parser->getOptions()->getMath();
		} else {
			$parser->getOptions()->optionUsed( 'math' );
		}

		$renderer = MathRenderer::getRenderer( $content, $attributes, $mode );

		$checkResult = $renderer->checkTex();

		if ( $checkResult !== true ) {
			// Returns the error message
			return $renderer->getLastError();
		}

		if ( $renderer->render() ) {
			LoggerFactory::getInstance( 'Math' )->info( "Rendering successful. Writing output" );
			$renderedMath = $renderer->getHtmlOutput();
		} else {
			LoggerFactory::getInstance( 'Math' )->warning(
				"Rendering failed. Printing error message." );
			return $renderer->getLastError();
		}
		Hooks::run( 'MathFormulaPostRender',
			array( $parser, &$renderer, &$renderedMath ) );// Enables indexing of math formula
		$parser->getOutput()->addModuleStyles( array( 'ext.math.styles' ) );
		if ( $mode == 'mathml' ) {
			$parser->getOutput()->addModuleStyles( array( 'ext.math.desktop.styles' ) );
			$parser->getOutput()->addModules( array( 'ext.math.scripts' ) );
		}
		// Writes cache if rendering was successful
		$renderer->writeCache();

		return array( $renderedMath, "markerType" => 'nowiki' );
	}

	/**
	 * Add the new math rendering options to Special:Preferences.
	 *
	 * @param $user Object: current User object
	 * @param $defaultPreferences Object: Preferences object
	 * @return Boolean: true
	 */
	static function onGetPreferences( $user, &$defaultPreferences ) {
		global $wgDefaultUserOptions;
		$defaultPreferences['math'] = array(
			'type' => 'radio',
			'options' => array_flip( self::getMathNames() ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		);
		// If the default option is not in the valid options the
		// user interface throws an exception (BUG 64844)
		$mode = MathHooks::mathModeToString( $wgDefaultUserOptions['math'] );
		if ( ! in_array( $mode, MathRenderer::getValidModes() ) ) {
			LoggerFactory::getInstance( 'Math' )->error( 'Misconfiguration: '.
				"\$wgDefaultUserOptions['math'] is not in " . MathRenderer::getValidModes() . ".\n".
				"Please check your LocalSetting.php file." );
			// Display the checkbox in the first option.
			$validModes = MathRenderer::getValidModes();
			$wgDefaultUserOptions['math'] = $validModes[0];
		}
		return true;
	}

	/**
	 * List of message keys for the various math output settings.
	 *
	 * @return array of strings
	 */
	public static function getMathNames() {
		$names = array();
		foreach ( MathRenderer::getValidModes() as $mode ) {
			$names[$mode] = wfMessage( 'mw_math_' . $mode )->escaped();
		}

		return $names;
	}

	/**
	 * MaintenanceRefreshLinksInit handler; optimize settings for refreshLinks batch job.
	 *
	 * @param Maintenance $maint
	 * @return boolean hook return code
	 */
	static function onMaintenanceRefreshLinksInit( $maint ) {
		global $wgUser;

		# Don't generate TeX PNGs (the lack of a sensible current directory causes errors anyway)
		$wgUser->setOption( 'math', 'source' );

		return true;
	}

	/**
	 * LoadExtensionSchemaUpdates handler; set up math table on install/upgrade.
	 *
	 * @param $updater DatabaseUpdater
	 * @throws Exception
	 * @return bool
	 */
	static function onLoadExtensionSchemaUpdates( $updater = null ) {

		if ( is_null( $updater ) ) {
			throw new Exception( 'Math extension is only necessary in 1.18 or above' );
		}

		$map = array( 'mysql', 'sqlite', 'postgres', 'oracle', 'mssql' );

		$type = $updater->getDB()->getType();

		if ( !in_array( $type, $map ) ) {
			throw new Exception( "Math extension does not currently support $type database." );
		}
		$sql = __DIR__ . '/db/math.' . $type . '.sql';
		$updater->addExtensionTable( 'math', $sql );
		if ( in_array( 'latexml', MathRenderer::getValidModes() ) ) {
			if ( in_array( $type, array( 'mysql', 'sqlite', 'postgres' ) ) ) {
				$sql = __DIR__ . '/db/mathlatexml.' . $type . '.sql';
				$updater->addExtensionTable( 'mathlatexml', $sql );
				if ( $type == 'mysql' ) {
					$sql = __DIR__ . '/db/patches/mathlatexml.mathml-length-adjustment.mysql.sql';
					$updater->modifyExtensionField( 'mathlatexml', 'math_mathml', $sql );
				}
			} else {
				throw new Exception( "Math extension does not currently support $type database for LaTeXML." );
			}
		}
		if ( in_array( 'mathml', MathRenderer::getValidModes() ) ) {
			if ( in_array( $type, array( 'mysql', 'sqlite', 'postgres' ) ) ) {
				$sql = __DIR__ . '/db/mathoid.' . $type . '.sql';
				$updater->addExtensionTable( 'mathoid', $sql );
			} else {
				throw new Exception( "Math extension does not currently support $type database for Mathoid." );
			}
		}

		return true;
	}

	/**
	 * Add 'math' and 'mathlatexml' tables to the list of tables that need to be copied to
	 * temporary tables for parser tests to run.
	 *
	 * @param array $tables
	 * @return bool
	 */
	static function onParserTestTables( &$tables ) {
		$tables[] = 'math';
		$tables[] = 'mathlatexml';
		return true;
	}

	/**
	 * Links to the unit test files for the test cases.
	 *
	 * @param string $files
	 * @return boolean (true)
	 */
	static function onRegisterUnitTests( &$files ) {
		$testDir = __DIR__ . '/tests/';
		$files = array_merge( $files, glob( "$testDir/*Test.php" ) );

		return true;
	}

	/**
	 *
	 * @global type $wgOut
	 * @param type $toolbar
	 */
	static function onEditPageBeforeEditToolbar( &$toolbar ) {
		global $wgOut;
		$wgOut->addModules( array( 'ext.math.editbutton.enabler' ) );
	}

	public static function registerExtension() {
		global $wgDefaultUserOptions, $wgMathValidModes, $wgMathDisableTexFilter;
		$wgMathValidModes = MathRenderer::getValidModes();
		if ( $wgMathDisableTexFilter == true ) { // ensure backwards compatibility
			$wgMathDisableTexFilter = 1;
		}
		$wgMathDisableTexFilter = MathRenderer::getDisableTexFilter();
		$wgDefaultUserOptions['math'] = self::mathModeToString( $wgDefaultUserOptions['math'] );
	}

	public static function onWikibaseRepoDataTypes( array &$dataTypeDefinitions ) {
		$newStringParser = function( ParserOptions $options ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$normalizer = new WikibaseStringValueNormalizer( $repo->getStringNormalizer() );
			return new StringParser( $normalizer );
		};
		$dataTypeDefinitions['PT:math'] = array(
				'value-type'                 => 'string',
				'validator-factory-callback' => function() {
					$factory = WikibaseRepo::getDefaultValidatorBuilders();
					return $factory->buildStringValidators();
				},
				'parser-factory-callback' => $newStringParser,
				'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
					// $factory = WikibaseRepo::getDefaultFormatterBuilders();
					// return $factory->newStringFormatter( $format, $options );
					return new MathFormatter( new StringFormatter( $options ), 'wfEscapeWikiText' );
				},
		);
	}
	public static function onWikibaseClientDataTypes( array &$dataTypeDefinitions ) {
		self::onWikibaseRepoDataTypes($dataTypeDefinitions);
	}
}
