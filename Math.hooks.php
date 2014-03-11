<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

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
		if ( ! is_callable( 'ParserOptions::getMath' ) && in_array( 'math', $forOptions) ) {
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
				if ( preg_match( '/(^|!)' . self::mathCacheKey.'[*\d]m?(!|$)/', $confstr ) ) {
					$confstr = preg_replace(
						'/(^|!)' . self::mathCacheKey . '[*\d]m?(!|$)/',
						'\1' . self::mathCacheKey . $mathOption . '\2',
						$confstr
					);
				} else {
					$confstr .= '!' . self::mathCacheKey . $mathOption;
				}

				wfDebugLog( 'Math', "New cache key: $confstr" );
			} else {
				wfDebugLog( 'Math', "Cache key found $confstr" );
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
	 * @return string
	 */
	static function mathTagHook( $content, $attributes, $parser ) {
		global $wgUseMathJax, $wgMathDisableTexFilter;

		if ( trim( $content ) === '' ) { // bug 8372
			return '';
		}

		wfProfileIn( __METHOD__ );
		$mode = (int)$parser->getUser()->getOption( 'math' );

		// Indicate that this page uses math.
		// This affects the page caching behavior.
		if ( is_callable( 'ParserOptions::getMath' ) ) {
			$parser->getOptions()->getMath();
		} else {
			$parser->getOptions()->optionUsed( 'math' );
		}

		$renderer = MathRenderer::getRenderer( $content, $attributes, $mode );

		if ( !$wgMathDisableTexFilter ) {
			$checkResult = $renderer->checkTex();

			if ( $checkResult !== true ) {
				// Returns the error message
				return $renderer->getLastError();
			}
		}

		$renderedMath = $renderer->render();

		if ( $wgUseMathJax && $mode == MW_MATH_MATHJAX ) {
			$parser->getOutput()->addModules( array( 'ext.math.mathjax.enabler' ) );
		}

		$renderer->writeCache();
		wfProfileOut( __METHOD__ );

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
		$defaultPreferences['math'] = array(
			'type' => 'radio',
			'options' => array_flip( self::getMathNames() ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		);

		return true;
	}

	/**
	 * List of message keys for the various math output settings.
	 *
	 * @return array of strings
	 */
	private static function getMathNames() {
		global $wgUseMathJax, $wgUseLaTeXML;

		$names = array(
			MW_MATH_PNG => wfMessage( 'mw_math_png' )->escaped(),
			MW_MATH_SOURCE => wfMessage( 'mw_math_source' )->escaped(),
		);

		if ( $wgUseMathJax ) {
			$names[MW_MATH_MATHJAX] = wfMessage( 'mw_math_mathjax' )->escaped();
		}

		if ( $wgUseLaTeXML ) {
			$names[MW_MATH_LATEXML] = wfMessage( 'mw_math_latexml' )->escaped();
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
	 * @throws MWException
	 * @return bool
	 */
	static function onLoadExtensionSchemaUpdates( $updater = null ) {
		if ( is_null( $updater ) ) {
			throw new MWException( 'Math extension is only necessary in 1.18 or above' );
		}

		$map = array(
			'mysql' => 'math.sql',
			'sqlite' => 'math.sql',
			'postgres' => 'math.pg.sql',
			'oracle' => 'math.oracle.sql',
			'mssql' => 'math.mssql.sql',
		);

		$type = $updater->getDB()->getType();

		if ( isset( $map[$type] ) ) {
			$sql = dirname( __FILE__ ) . '/db/' . $map[$type];
			$updater->addExtensionTable( 'math', $sql );
		} else {
			throw new MWException( "Math extension does not currently support $type database." );
		}

		return true;
	}

	/**
	 * Add 'math' table to the list of tables that need to be copied to
	 * temporary tables for parser tests to run.
	 *
	 * @param array $tables
	 * @return bool
	 */
	static function onParserTestTables( &$tables ) {
		$tables[] = 'math';

		return true;
	}

	/**
	 * Hack to fake a default $wgMathPath value so parser test output
	 * that renders to images doesn't vary by who runs it.
	 *
	 * @global string $wgMathPath
	 * @param Parser $parser
	 * @return bool
	 */
	static function onParserTestParser( &$parser ) {
		global $wgMathPath;

		$wgMathPath = '/images/math';

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
}
