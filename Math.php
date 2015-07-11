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
 * @author Derk-Jan Hartman
 * @copyright © 2002-2012 various MediaWiki contributors
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
	'version' => '2.0.0',
	'author' => array(
		'Tomasz Wegrzanowski',
		'Brion Vibber',
		'Moritz Schubotz',
		'Derk-Jan Hartman',
	),
	'descriptionmsg' => 'math-desc',
	'url' => 'https://www.mediawiki.org/wiki/Extension:Math',
);

/**@{
 * Maths constants
 */
define( 'MW_MATH_PNG',    0 );
define( 'MW_MATH_SIMPLE', 1 ); /// @deprecated
define( 'MW_MATH_HTML',   2 ); /// @deprecated
define( 'MW_MATH_SOURCE', 3 );
define( 'MW_MATH_MODERN', 4 ); /// @deprecated
define( 'MW_MATH_MATHML', 5 );
define( 'MW_MATH_MATHJAX', 6 ); /// @deprecated
define( 'MW_MATH_LATEXML', 7 ); /// new in 1.22
define( 'MW_MATH_LATEXML_JAX', 8 ); /// new in 1.22
/**@}*/

/**@{
 * Mathstyle constants
 */
define( 'MW_MATHSTYLE_INLINE_DISPLAYSTYLE',  0 ); //default large operator inline
define( 'MW_MATHSTYLE_DISPLAY', 1 ); // large operators centered in a new line
define( 'MW_MATHSTYLE_INLINE',  2 ); // small operators inline
define( 'MW_MATHSTYLE_LINEBREAK',  3 ); // break long lines (experimental)
// There is no style which renders small operators
// but display the equation centered in a new line.
/**@}*/

/**@var array defines the mode allowed on the server */
$wgMathValidModes = array( MW_MATH_PNG, MW_MATH_SOURCE, MW_MATH_MATHML );

/*
 * The default rendering mode for anonymous users.
 * Valid options are defined in $wgMathValidModes.
 */
$wgDefaultUserOptions['math'] = MW_MATH_PNG;

/** Location of the texvc binary */
$wgTexvc = __DIR__ . '/math/texvc';
/**
 * Texvc background color
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
$wgMathCheckFiles = true;

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

/**
 * The url of the mathoid server.
 *
 * Documentation: http://www.formulasearchengine.com/mathoid
 * Example value: http://mathoid.example.org:10042
 *
 * @todo Move documentation to mediawiki.org
 */
$wgMathMathMLUrl = 'http://mathoid.testme.wmflabs.org';

/**
 * The timeout for the HTTP-Request sent to the MathML to render an equation,
 * in seconds.
 */
$wgMathMathMLTimeout = 20;

/**
 * Use of LaTeXML for details see
 * <http://latexml.mathweb.org/help>
 *
 * If you want or need to run your own server, follow these installation
 * instructions and override $wgMathLaTeXMLUrl:
 * <http://www.formulasearchengine.com/LaTeXML>
 *
 * If you expect heavy load you can specify multiple servers. In that case one
 * server is randomly chosen for each rendering process. Specify the list of
 * servers in an array e.g $wgMathLaTeXMLUrl = array ( 'http://latexml.example.com/convert',
 * 'http://latexml2.example.com/convert');
 */
$wgMathLaTeXMLUrl = 'http://gw125.iu.xsede.org:8888'; // Sponsored by https://www.xsede.org/

/**
 * The timeout for the HTTP-Request sent to the LaTeXML to render an equation,
 * in seconds.
 */
$wgMathLaTeXMLTimeout = 240;
/**
 * Setting for the LaTeXML renderer.
 * See http://dlmf.nist.gov/LaTeXML/manual/commands/latexmlpost.xhtml for details.
 */
$wgMathDefaultLaTeXMLSetting = array(
	'format' => 'xhtml',
	'whatsin' => 'math',
	'whatsout' => 'math',
	'pmml',
	'cmml',
	'mathtex',
	'nodefaultresources',
	'preload' => array( 'LaTeX.pool',
		'article.cls',
		'amsmath.sty',
		'amsthm.sty',
		'amstext.sty',
		'amssymb.sty',
		'eucal.sty',
		'[dvipsnames]xcolor.sty',
		'url.sty',
		'hyperref.sty',
		'[ids]latexml.sty',
		'texvc' ),
	'linelength' => 90
);
/**
 * The link to the texvccheck executable
 */
$wgMathTexvcCheckExecutable = __DIR__ . '/texvccheck/texvccheck';

/**@{
 * Math check constants
 */
define( 'MW_MATH_CHECK_ALWAYS', 0 ); /// backwards compatible to false
define( 'MW_MATH_CHECK_NEVER' , 1 ); /// backwards compatible to true
define( 'MW_MATH_CHECK_NEW'   , 2 );
/**@}*/
/**
 * Option to disable the TeX security filter:
 * In general every math object, which is rendered by the math extension has its rendering cached in
 * a database.
 * MW_MATH_CHECK_ALWAYS: If set to MW_MATH_CHECK_ALWAYS only a subset of the TeX commands is allowed.
 * See the Wikipedia page Help:Math for details about the allowed commands.
 * MW_MATH_CHECK_NONE: If set to MW_MATH_CHECK_NONE any TeX expression is parsed.
 * This can be a potential security risk.
 * MW_MATH_CHECK_NEW checks only new equations. If the database does not yet contain the given math object,
 * then it is passed through texvccheck.
 * Please make sure to truncate the database tables (math, mathoid, mathlatexml) when switching from
 * MW_MATH_CHECK_NONE to MW_MATH_CHECK_NEW. Otherwise, unchecked content contained in the database
 * will be displayed.
 */
$wgMathDisableTexFilter = MW_MATH_CHECK_NEW;

/** @var boolean $wgMathEnableExperimentalInputFormats enables experimental MathML and AsciiMath input format support */
$wgMathEnableExperimentalInputFormats = false;
////////// end of config settings.

$wgExtensionFunctions[] = 'MathHooks::setup';
$wgHooks['ParserFirstCallInit'][] = 'MathHooks::onParserFirstCallInit';
$wgHooks['GetPreferences'][] = 'MathHooks::onGetPreferences';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'MathHooks::onLoadExtensionSchemaUpdates';
$wgHooks['ParserTestTables'][] = 'MathHooks::onParserTestTables';
$wgHooks['UnitTestsList'][] = 'MathHooks::onRegisterUnitTests';
$wgHooks['PageRenderingHash'][] = 'MathHooks::onPageRenderingHash';
$wgHooks['EditPageBeforeEditToolbar'][] = 'MathHooks::onEditPageBeforeEditToolbar';

$dir = __DIR__ . '/';
$wgAutoloadClasses['MathHooks'] = $dir . 'Math.hooks.php';
$wgAutoloadClasses['MathRenderer'] = $dir . 'MathRenderer.php';
$wgAutoloadClasses['MathTexvc'] = $dir . 'MathTexvc.php';
$wgAutoloadClasses['MathSource'] = $dir . 'MathSource.php';
$wgAutoloadClasses['MathMathML'] = $dir . 'MathMathML.php';
$wgAutoloadClasses['MathLaTeXML'] = $dir . 'MathLaTeXML.php';
$wgAutoloadClasses['MathInputCheck'] = $dir . 'MathInputCheck.php';
$wgAutoloadClasses['MathInputCheckTexvc'] = $dir . 'MathInputCheckTexvc.php';
$wgAutoloadClasses['SpecialMathShowImage'] = $dir . 'SpecialMathShowImage.php';
$wgAutoloadClasses['SpecialMathStatus'] = $dir . 'SpecialMathStatus.php';
$wgMessagesDirs['Math'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['MathAlias'] = $dir . 'Math.alias.php';
$wgExtensionMessagesFiles['MathAliasNoTranslate'] = $dir . 'Math.alias.noTranslate.php';

$wgParserTestFiles[] = $dir . 'mathParserTests.txt';

$wgSpecialPages['MathShowImage'] = 'SpecialMathShowImage';
$wgSpecialPages['MathStatus'] = 'SpecialMathStatus';

$wgResourceModules['ext.math.styles'] = array(
	'position' => 'top',
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'Math/modules',
	'styles' => 'ext.math.css',
	'targets' => array( 'desktop', 'mobile' ),
);
$wgResourceModules['ext.math.desktop.styles'] = array(
	'position' => 'top',
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'Math/modules',
	'styles' => 'ext.math.desktop.css',
);
$wgResourceModules['ext.math.scripts'] = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'Math/modules',
	'scripts' => 'ext.math.js',
);


$moduleTemplate = array(
    'localBasePath' => __DIR__ . '/modules',
    'remoteExtPath' => 'Math/modules',
);

$wgResourceModules['ext.math.editbutton.enabler'] = array(
	'scripts' => 'ext.math.editbutton.js',
	'messages' => array(
		'math_tip',
		'math_sample',
	),
) + $moduleTemplate;

$wgResourceModules['ext.math.visualEditor'] = array(
	'scripts' => array(
		'VisualEditor/ve.dm.MWMathNode.js',
		'VisualEditor/ve.ce.MWMathNode.js',
		'VisualEditor/ve.ui.MWMathInspector.js',
		'VisualEditor/ve.ui.MWMathInspectorTool.js',
	),
	'styles' => array(
		'VisualEditor/ve.ce.MWMathNode.css',
		'VisualEditor/ve.ui.MWMathIcons.css',
		'VisualEditor/ve.ui.MWMathInspector.css',
	),
	'dependencies' => array(
		'ext.visualEditor.mwcore',
	),
	'messages' => array(
		'math-visualeditor-mwmathinspector-display',
		'math-visualeditor-mwmathinspector-display-block',
		'math-visualeditor-mwmathinspector-display-default',
		'math-visualeditor-mwmathinspector-display-inline',
		'math-visualeditor-mwmathinspector-id',
		'math-visualeditor-mwmathinspector-title',
	),
	'targets' => array( 'desktop', 'mobile' ),
) + $moduleTemplate;

$wgVisualEditorPluginModules[] = 'ext.math.visualEditor';
