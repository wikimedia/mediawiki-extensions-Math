<?php
/**
 * MediaWiki math extension
 *
 * @file
 * @ingroup Extensions
 * @version 2.0
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
 * @author Moritz Schubotz
 * @copyright © 2002-2013 various MediaWiki contributors
 * @license GPLv2 license; info in main package.
 * @link http://www.mediawiki.org/wiki/Extension:Math Documentation
 * @see https://bugzilla.wikimedia.org/show_bug.cgi?id=14202
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( "This is not a valid entry point to MediaWiki.\n" );
}

// Extension credits that will show up on Special:Version
$wgExtensionCredits['parserhook'][] = array(
	'path' => __FILE__,
	'name' => 'Math',
	'version' => '2.0',
	'author' => array( 'Tomasz Wegrzanowski', 'Brion Vibber', 'Moritz Schubotz', '...' ),
	'descriptionmsg' => 'math-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Math',
);

/**@{
 * Maths constants
 */
define( 'MW_MATH_PNG',    0 ); /// @deprecated
define( 'MW_MATH_SIMPLE', 1 ); /// @deprecated
define( 'MW_MATH_HTML',   2 ); /// @deprecated
define( 'MW_MATH_SOURCE', 3 );
define( 'MW_MATH_MODERN', 4 ); /// @deprecated
define( 'MW_MATH_MATHML', 5 );
define( 'MW_MATH_MATHJAX', 6); /// @deprecated
/**@}*/

/** Stores debug information in the database and proviedes more detailed debug output*/
$wgMathDebug = false;
/**
 * Experimental option to use MathJax library to do client-side math rendering
 * when JavaScript is available. In supporting browsers this makes nice output
 * that's scalable for zooming, printing, and high-resolution displays.
 *
 * Not guaranteed to be stable at this time.
 */
$wgMathJax = true;

/**
 * Use of MathML for details see
 * modules/svgtex/readme.md
 */
$wgMathMathMLUrl = 'http://localhost:16000';
/* enable to use MathML */
$wgMathUseMathML = true;
/**
 * The timeout for the HTTP-Request sent to the MathML to render an equation,
 * in seconds.
 */
$wgMathMathMLTimeout = 2;
/**
 * Option to disable the tex filter. If set to true any LaTeX espression is parsed
 * this can be a potential security risk. If set to false only a subset of the tex
 * commands is allowed. See the wikipedia page Help:Math for details.
 */
$wgMathDisableTexFilter = false;

/**
 * The link to the texvc executable
 * TODO: Replace that by an latex grammar implemented in php
 */
$wgMathTexvcCheckExecutable = dirname( __FILE__ ) . '/texvccheck/texvccheck';

////////// end of config settings.
/*
 * The default rendering mode for anonymous users.
 */
$wgDefaultUserOptions['math'] = MW_MATH_MATHML;

$wgExtensionFunctions[] = 'MathHooks::setup';
$wgHooks['ParserFirstCallInit'][] = 'MathHooks::onParserFirstCallInit';
$wgHooks['GetPreferences'][] = 'MathHooks::onGetPreferences';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'MathHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserTestTables'][] = 'MathHooks::onParserTestTables';
$wgHooks['ParserTestParser'][] = 'MathHooks::onParserTestParser';
$wgHooks['UnitTestsList'][] = 'MathHooks::onRegisterUnitTests';

$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['MathHooks'] = $dir . 'Math.hooks.php';
$wgAutoloadClasses['MathRenderer'] = $dir . 'MathRenderer.php';
$wgAutoloadClasses['MathSource'] = $dir . 'MathSource.php';
$wgAutoloadClasses['MathMathML'] = $dir . 'MathMathML.php';
$wgAutoloadClasses['MathMathMLLocal'] = $dir . 'MathMathMLLocal.php';
$wgAutoloadClasses['MathInputCheck'] = $dir . 'MathInputCheck.php';
$wgAutoloadClasses['MathInputCheckTexvc'] = $dir . 'MathInputCheckTexvc.php';
$wgAutoloadClasses['SpecialMathShowImage'] = $dir . 'SpecialMathShowImage.php';
$wgAutoloadClasses['MathSvg'] = $dir . 'MathSvg.php';

$wgExtensionMessagesFiles['Math'] = $dir . 'Math.i18n.php';
$wgExtensionMessagesFiles['MathAlias'] = $dir . 'Math.alias.php';

$wgParserTestFiles[] = $dir . 'mathParserTests.txt';

//TODO: Is that needed since the page should not be listed
#$wgSpecialPageGroups['MathShowImage'] = 'math';
$wgSpecialPages['MathShowImage'] = 'SpecialMathShowImage';

$wgResourceModules['ext.math.styles'] = array(
	'styles' => 'ext.math.css',

	// You need to declare the base path of the file paths in 'scripts' and 'styles'
	'localBasePath' => __DIR__,
	// ... and the base from the browser as well. For extensions this is made easy,
	// you can use the 'remoteExtPath' property to declare it relative to where the wiki
	// has $wgExtensionAssetsPath configured:
	'remoteExtPath' => 'Math'
);

$moduleTemplate = array(
	'localBasePath' => dirname( __FILE__ ) . '/modules',
	'remoteExtPath' => 'Math/modules',
);

$wgResourceModules['ext.math.mathjax'] = array(
	'scripts' => array(
		'MathJax/MathJax.js',
		// We'll let the other parts be loaded by MathJax's
		// own module/config loader.
	),
	'group' => 'ext.math.mathjax',
) + $moduleTemplate;

$wgResourceModules['ext.math.mathjax.enabler'] = array(
	'scripts' => 'ext.math.mathjax.enabler.js',
) + $moduleTemplate;

//DEPRECATED SETTINGS WILL BE DELETED
/** For back-compat */
$wgUseTeX = true; //TODO: find out why we need this

/** Allows to use the deprecated texvc libraries*/
$wgMathUseTexvc = true;


/** @deprecated since version  2.0 Location of the texvc binary */
$wgTexvc = dirname( __FILE__ ) . '/math/texvc';

/* Texvc background color
 * use LaTeX color format as used in \special function
 * for transparent background use value 'Transparent' for alpha transparency or
 * 'transparent' for binary transparency.
 */
$wgTexvcBackgroundColor = 'transparent';

/**
 * Normally when generating math images, we double-check that the
 * directories we want to write to exist, and that files that have
 * been generated still exist when we need to bring them up again.
 *
 * This lets us give useful error messages in case of permission
 * problems, and automatically rebuild images that have been lost.
 *
 * On a big site with heavy NFS traffic this can be slow and flaky,
 * so sometimes we want to short-circuit it by setting this to false.
 */
$wgMathCheckFiles = false;

/**
 * The URL path of the math directory. Defaults to "{$wgUploadPath}/math".
 *
 * See http://www.mediawiki.org/wiki/Manual:Enable_TeX for details about how to
 * set up mathematical formula display.
 */
$wgMathPath = false;

/**
 * The name of a file backend ($wgFileBackends) to use for storing math renderings.
 * Defaults to FSFileBackend using $wgMathDirectory as a base path.
 *
 * See http://www.mediawiki.org/wiki/Manual:Enable_TeX for details about how to
 * set up mathematical formula display.
 */
$wgMathFileBackend = false;

/**
 * The filesystem path of the math directory.
 * Defaults to "{$wgUploadDirectory}/math".
 *
 * See http://www.mediawiki.org/wiki/Manual:Enable_TeX for details about how to
 * set up mathematical formula display.
 */
$wgMathDirectory = false;

$wgAutoloadClasses['MathTexvc'] = $dir . 'MathTexvc.php';