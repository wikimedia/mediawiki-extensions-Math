<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2013 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

class MathHooks {
	/*
	 *
	 * @param &$confstr The to-be-hashed key string that is being constructed
	 */
	public static function onPageRenderingHash( &$confstr ) {
		global $wgUser,$wgMathJax;
		$user = $wgUser;
		$confstr .= "!" . $user->getOption('math');
		if ( $wgMathJax &&  $user->getOption('mathJax') ){
			$confstr .= "!" . 1;
		}
		wfDebugLog('Math', 'New cache key'.$confstr);
		return true;
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
	 * @param $content the LaTeX input
	 * @param $attributes
	 * @param Parser $parser
	 * @return string
	 */
	static function mathTagHook( $content, $attributes, $parser ) {
		global $wgMathJax, $wgMathDisableTexFilter;
		if ( trim( $content ) === "" ) { // bug 8372
			return "";
		}
		wfProfileIn( __METHOD__ );
		$mode = (int) $parser->getUser()->getOption('math');
		$parser->getOptions()->addExtraKey($mode);
		$renderer = MathRenderer::getRenderer(
			$content, $attributes, $mode
		);
		if (! $wgMathDisableTexFilter){
			$checkResult = $renderer->checkTex();
			if ( $checkResult !== true ){
				//returns the error message
				return $renderer->getLastError();
			}
		}
		if ( $renderer->render() ){
			wfDebugLog( "Math" , "Rendering successfull. Writing output");
			$renderedMath = $renderer->getHtmlOutput();
		} else {
			wfDebugLog( "Math" , "Rendering failed. Prining error message.");
			return $renderer->getLastError();
		}
		wfRunHooks( 'MathFormulaRendered',
			array( &$renderer,
				&$renderedMath,
				$parser->getTitle()->getArticleID(),
				$parser->nextLinkID()) );//Enables indexing of math formula
		if ( $wgMathJax && $parser->getUser()->getOption('mathJax')) {
			//maybe this can be checked in the javascript, this would be better for caching
			$parser->getOptions()->addExtraKey(1);
			$parser->getOutput()->addModules( array( 'ext.math.mathjax.enabler' ) );
		}
		$parser->getOutput()->addModuleStyles( array( 'ext.math.styles' ) );
		$parser->getOutput()->addModuleStyles( array( 'ext.math.styles.fonts' ) );
		//$parser->getOutput()->addModules( array( 'ext.math.selector' ) );

		// Writes cache if rendering was successful
		$renderer->writeCache();
		//Check if we needed that MathRenderer::armourMath( $renderedMath);
		wfProfileOut( __METHOD__ );
		return array($renderedMath, "markerType" => 'nowiki' );
	}

	/**
	 * Add the new math rendering options to Special:Preferences.
	 *
	 * @param $user Object: current User object
	 * @param $defaultPreferences Object: Preferences object
	 * @return Boolean: true
	 */
	static function onGetPreferences( $user, &$defaultPreferences ) {
		global $wgMathJax;
		$defaultPreferences['math'] = array(
			'type' => 'radio',
			'options' => array_flip( self::getMathNames() ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		);
		if ( $wgMathJax ) {
			$defaultPreferences['mathJax'] = array(
				'type' => 'toggle',
				'label-message' => 'mw_math_mathjax',
				'section' => 'rendering/math',
			);
		}
		return true;
	}

	/**
	 * List of message keys for the various math output settings.
	 *
	 * @return array of strings
	 */
	private static function getMathNames() {
		global $wgMathValidModes;
		$MathConstantNames = array(
			MW_MATH_SOURCE => 'mw_math_source',
			MW_MATH_PNG => 'mw_math_png',
			MW_MATH_MATHML => 'mw_math_mathml',
			MW_MATH_LATEXML => 'mw_math_latexml'
		);
		$names=array();
		foreach ($wgMathValidModes as $mode) {
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

		# Don't generate TeX PNGs (lack of a sensible current directory causes errors anyway)
		//TODO: revalidate that
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
		global $wgMathDebug;
		if ( is_null( $updater ) ) {
			throw new MWException( "Math extension is only necessary in 1.18 or above" );
		}
		$map = array( 'mysql', 'sqlite', 'postgres', 'oracle', 'mssql');
		$type = $updater->getDB()->getType();
		if ( $type == 'sqlite' ){
			$type = 'mysql'; //The commands used from the updater are the same
		}
		if (in_array( $type, $map ) ){
			$sql = dirname( __FILE__ ) . '/db/math.' . $type .'.sql';
			$updater->addExtensionTable( 'math', $sql );
			$sql = dirname( __FILE__ ) . '/db/mathoid.' . $type .'.sql';
			$updater->addExtensionTable( 'mathoid', $sql );
		} else {
			throw new MWException( "Math extension does not currently support $type database.\n" );
		}
		if ($wgMathDebug){
			if($type =='mysql' ){
				$dir = dirname( __FILE__ ) . '/db/debug_fields_';
				$updater->addExtensionField('mathoid', 'math_status', $dir.'math_status.sql');
				$updater->addExtensionField('mathoid', 'math_timestamp', $dir.'math_timestamp.sql');
				$updater->addExtensionField('mathoid', 'math_log', $dir.'math_log.sql');
			} else {
				throw new MWException( "Math extension does not currently support $type database for debugging.\n"
					. 'Please set $wgMathDebug = false; in your LocalSettings.php' );
			}
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
		$tables[] = 'mathoid';
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
	 * Hack to fake a default $wgMathPath value so parser test output
	 * that renders to images doesn't vary by who runs it.
	 *
	 * @global string $wgMathPath
	 * @param Parser $parser
	 * @return bool
	 * @deprecated since version 2.0
	 */
	static function onParserTestParser( &$parser ) {
		global $wgMathPath;
		$wgMathPath = '/images/math';
		return true;
	}

	/**
	 * Set up $wgMathPath and $wgMathDirectory globals if they're not already
	 * set.
	 * @deprecated since version 2.0
	 */
	static function setup() {
		global $wgMathPath, $wgMathDirectory;
		global $wgUploadPath, $wgUploadDirectory;
		if ( $wgMathPath === false ) {
			$wgMathPath = "{$wgUploadPath}/math";
		}
		if ( $wgMathDirectory === false ) {
			$wgMathDirectory = "{$wgUploadDirectory}/math";
		}
	}
}
