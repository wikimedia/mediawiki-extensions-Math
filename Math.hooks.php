<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2015 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

use MediaWiki\Logger\LoggerFactory;

class MathHooks {
	const mathCacheKey = 'math=';

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
		if ( ! is_callable( 'ParserOptions::getMath' ) && in_array( 'math', $forOptions ) ) {
			if ( $user === false ) {
				$user = $wgUser;
			}

			$mathOption = $user->getOption( 'math' );
			// Check if the key already contains the math option part
			if (
				!preg_match(
					'/(^|!)' . self::mathCacheKey . $mathOption . '(!|$)/',
					$confstr
				)
			) {
				// The math part of cache key starts with "math=" followed by a star or a number for the math mode
				// and the optional letter j that indicates if clientside MathJax rendering is used.
				if ( preg_match( '/(^|!)' . self::mathCacheKey . '[*\d]m?(!|$)/', $confstr ) ) {
					$confstr = preg_replace(
						'/(^|!)' . self::mathCacheKey . '[*\d]m?(!|$)/',
						'\1' . self::mathCacheKey . $mathOption . '\2',
						$confstr
					);
				} else {
					$confstr .= '!' . self::mathCacheKey . $mathOption;
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

		$mode = (int)$parser->getUser()->getOption( 'math' );

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
		if ( $mode == MW_MATH_MATHJAX || $mode == MW_MATH_LATEXML_JAX ) {
			$parser->getOutput()->addModules( array( 'ext.math.mathjax.enabler' ) );
		}
		$parser->getOutput()->addModuleStyles( array( 'ext.math.styles' ) );
		if ( $mode == MW_MATH_MATHML ) {
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
		global $wgMathValidModes, $wgDefaultUserOptions;
		$defaultPreferences['math'] = array(
			'type' => 'radio',
			'options' => array_flip( self::getMathNames() ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		);
		// If the default option is not in the valid options the
		// user interface throws an exception (BUG 64844)
		if ( ! in_array( $wgDefaultUserOptions['math'] , $wgMathValidModes ) ) {
			LoggerFactory::getInstance( 'Math' )->error( 'Misconfiguration: '.
				"\$wgDefaultUserOptions['math'] is not in \$wgMathValidModes.\n".
				"Please check your LocalSetting.php file." );
			// Display the checkbox in the first option.
			$wgDefaultUserOptions['math'] = $wgMathValidModes[0];
		}
		return true;
	}

	/**
	 * List of message keys for the various math output settings.
	 *
	 * @return array of strings
	 */
	public static function getMathNames() {
		global $wgMathValidModes;
		$MathConstantNames = array(
			MW_MATH_SOURCE => 'mw_math_source',
			MW_MATH_PNG => 'mw_math_png',
			MW_MATH_MATHML => 'mw_math_mathml',
			MW_MATH_LATEXML => 'mw_math_latexml',
			MW_MATH_LATEXML_JAX => 'mw_math_latexml_jax',
			MW_MATH_MATHJAX => 'mw_math_mathjax'
		);
		$names = array();
		foreach ( $wgMathValidModes as $mode ) {
			$names[$mode] = wfMessage( $MathConstantNames[$mode] )->escaped();
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
		$wgUser->setOption( 'math', MW_MATH_SOURCE );

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
		global $wgMathValidModes;
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
		if ( in_array( MW_MATH_LATEXML, $wgMathValidModes ) ) {
			if ( in_array( $type, array( 'mysql', 'sqlite', 'postgres' ) ) ) {
				$sql = __DIR__ . '/db/mathlatexml.' . $type . '.sql';
				$updater->addExtensionTable( 'mathlatexml', $sql );
				if ( $type == 'mysql' ){
					$sql = __DIR__ . '/db/patches/mathlatexml.mathml-length-adjustment.mysql.sql';
					$updater->modifyExtensionField( 'mathlatexml', 'math_mathml', $sql );
				}
			} else {
				throw new Exception( "Math extension does not currently support $type database for LaTeXML." );
			}
		}
		if ( in_array( MW_MATH_MATHML, $wgMathValidModes ) ) {
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
}
