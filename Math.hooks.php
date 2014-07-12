<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2014 various MediaWiki contributors
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
	 * @return array
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
		if ( $renderer->render() ) {
			wfDebugLog( "Math" , "Rendering successful. Writing output" );
			$renderedMath = $renderer->getHtmlOutput();
		} else {
			wfDebugLog( "Math" , "Rendering failed. Printing error message." );
			return $renderer->getLastError();
		}
		wfRunHooks( 'MathFormulaRendered',
			array( &$renderer,
				&$renderedMath,
				$parser->getTitle()->getArticleID(),
				$parser->nextLinkID() ) );// Enables indexing of math formula
		if ( $wgUseMathJax ) {
			$parser->getOutput()->addModules( array( 'ext.math.mathjax.enabler' ) );
		}
		$parser->getOutput()->addModuleStyles( array( 'ext.math.styles' ) );

		// Writes cache if rendering was successful
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
		global $wgUseMathJax, $wgMathValidModes, $wgDefaultUserOptions;
		$defaultPreferences['math'] = array(
			'type' => 'radio',
			'options' => array_flip( self::getMathNames() ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		);
		if ( $wgUseMathJax ) {
			$defaultPreferences['mathJax'] = array(
				'type' => 'toggle',
				'label-message' => 'mw_math_mathjax',
				'section' => 'rendering/math',
			);
		}
		// If the default option is not in the valid options the
		// user interface throws an exception (BUG 64844)
		if ( ! in_array( $wgDefaultUserOptions['math'] , $wgMathValidModes ) ){
			wfDebugLog( 'Math', "Warning: Misconfiguration \$wgDefaultUserOptions['math'] is not in \$wgMathValidModes. Please check your LocalSetting.php file.");
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
			MW_MATH_LATEXML => 'mw_math_latexml'
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
	 * @throws MWException
	 * @return bool
	 */
	static function onLoadExtensionSchemaUpdates( $updater = null ) {
		global $wgMathValidModes;
		if ( is_null( $updater ) ) {
			throw new MWException( 'Math extension is only necessary in 1.18 or above' );
		}

		$map = array( 'mysql', 'sqlite', 'postgres', 'oracle', 'mssql' );

		$type = $updater->getDB()->getType();

		if ( in_array( $type, $map ) ) {
			$sql = dirname( __FILE__ ) . '/db/math.' . $type . '.sql';
			$updater->addExtensionTable( 'math', $sql );
			if ( in_array( MW_MATH_LATEXML, $wgMathValidModes ) ) {
				if ( in_array( $type, array( 'mysql', 'sqlite', 'postgres' ) ) ) {
					$sql = dirname( __FILE__ ) . '/db/mathlatexml.' . $type . '.sql';
					$updater->addExtensionTable( 'mathlatexml', $sql );
				} else {
					throw new MWException( "Math extension does not currently support $type database for LaTeXML." );
				}
			}
			if ( in_array( MW_MATH_MATHML, $wgMathValidModes ) ) {
				if ( in_array( $type, array( 'mysql', 'sqlite', 'postgres' ) ) ) {
					$sql = dirname( __FILE__ ) . '/db/mathoid.' . $type . '.sql';
					$updater->addExtensionTable( 'mathoid', $sql );
				} else {
					throw new MWException( "Math extension does not currently support $type database for Mathoid." );
				}
			}
		} else {
			throw new MWException( "Math extension does not currently support $type database." );
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
