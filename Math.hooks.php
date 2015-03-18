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

				MWLoggerFactory::getInstance( 'Math' )->debug( "New cache key: $confstr" );
			} else {
				MWLoggerFactory::getInstance( 'Math' )->debug( "Cache key found: $confstr" );
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
			MWLoggerFactory::getInstance( 'Math' )->info( "Rendering successful. Writing output" );
			$renderedMath = $renderer->getHtmlOutput();
		} else {
			MWLoggerFactory::getInstance( 'Math' )->warning(
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
			MWLoggerFactory::getInstance( 'Math' )->error( 'Misconfiguration: '.
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
				if ( $type == 'mysql' ) {
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

	/**
	 * Implements the hook SpecialSearchSetupEngine
	 * Loads stylesheets required for displaying Mathematics
	 * @param SpecialPage $that
	 * @param $profile
	 * @param $search
	 */
	public static function onSpecialSearchSetupEngine( SpecialPage $that, $profile, $search ){
		$out = $that->getOutput();
		$out->addModuleStyles( array( 'ext.math.styles' ) );
		$out->addModuleStyles( array( 'ext.math.desktop.styles' ) );
		$out->addModules( array( 'ext.math.scripts' ) );
	}

	/**
	 * Implements the hook ShowSearchHit
	 * Searches for complete <code><math>$tex</math></code> tags and replaces them
	 * with their rendering. Partial tags like <code>text <math>$te</code> are ignored.
	 *
	 * @param $searchPage
	 * @param $result
	 * @param $terms
	 * @param $link
	 * @param $redirect
	 * @param $section
	 * @param string $extract the text snipped going to be displayed
	 * @param $score
	 * @param $size
	 * @param $date
	 * @param $related
	 * @param $html
	 * @return bool
	 */
	static function onShowSearchHit( $searchPage, $result, $terms, &$link, &$redirect, &$section,
		&$extract, &$score, &$size, &$date, &$related, &$html ) {
		// use site default math rendering mode
		global $wgDefaultUserOptions;
		$mathMode = $wgDefaultUserOptions['math'];
		if ( $mathMode == MW_MATH_MATHJAX ) { //Nobody wants to debug that.
			return true;
		}
		// find math tags
		$unescapedExtract = self::unEscapeHtmlTags( $extract );
		$unescapedExtract = Parser::extractTagsAndParams( array( 'math' ), $unescapedExtract, $mathTags );
		foreach ( $mathTags as $id => $tag ) {
			$unescapedContent = $tag[1];
			$attributes = $tag[2];
			$fullElement = $tag[3];
			if ( substr( $fullElement, - 7 ) == '</math>' ) { // only full elements
				$logger = MWLoggerFactory::getInstance( 'Math' );
				$content = self::escapeHtmlTags( $unescapedContent );
				// remove span highlighting
				$content = str_replace( '<span class="searchmatch">', '', $content );
				$content = str_replace( '</span>', '', $content );
				// unescape backslash
				$content = str_replace( "\\\\", "\\", $content );
				$renderer = MathRenderer::getRenderer( $content, $attributes, $mathMode );
				$logger = MWLoggerFactory::getInstance( 'Math' );
				if ( $renderer->checkTex() && $renderer->render() ) {
					$renderedMath = $renderer->getHtmlOutput();
					$renderer->writeCache(); // Math rendering takes a while
					// replace parser placeholders with math rendering
					$unescapedExtract = str_replace( $id, self::unEscapeHtmlTags( $renderedMath ),
						$unescapedExtract );
					$logger->info( "Display \\$$content\\$ in search results." );
				} else {
					$logger->warning( "Can not display \\$$content\\$ in search results. \n".
						var_export( $renderer->getLastError(), true ) );
				}
			}
			// undo parser modifications to unclosed math tags
			$unescapedExtract = str_replace( $id, $fullElement, $unescapedExtract );
		}
		$extract = self::escapeHtmlTags( $unescapedExtract );
		return true;
	}

	/**
	 * Helper function for @see onShowSearchHit.
	 * Replaces escaped HTML tags in output such as <code>&gt;math&lt;</code> with
	 * <code><math></code> in order to apply normal parser functions to get the tags from the
	 * snipped. HTML tags inserted for Highlighting by the search process such as
	 * <code><span class="searchmatch"></code> are escaped. For instance
	 * <code><</code> is translated to <code>-lt--QINU\x7f</code>.
	 *
	 * @param string $in
	 * @return string mixed
	 */
	private static function unEscapeHtmlTags( $in ){
		$out = $in;

		// Do not switch the order of these instructions
		$out = str_replace( '<',    "-lt--QINU\x7f", $out );
		$out = str_replace( '>',    "-gt--QINU\x7f", $out );

		$out = str_replace( '&lt;', '<'            , $out );
		$out = str_replace( '&gt;', '>'            , $out );

		return $out;
	}

	/**
	 * Helper function for @see onShowSearchHit.
	 *
	 * Reverts edits done by @see unEscapeHtmlTags.
	 * @param string $in
	 * @return string mixed
	 */
	private static function escapeHtmlTags( $in ){
		$out = $in;
		$out = str_replace( '<',            '&lt;', $out );
		$out = str_replace( '>',            '&gt;', $out );
		$out = str_replace( "-lt--QINU\x7f",'<',    $out );
		$out = str_replace( "-gt--QINU\x7f",'>',    $out );
		return $out;
	}
}