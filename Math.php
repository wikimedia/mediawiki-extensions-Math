<?php
/**
 * MediaWiki math extension
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
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
	'version' => '1.1',
		'author' => array( 'Tomasz Wegrzanowski', 'Brion Vibber', '...' ),
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
define( 'MW_MATH_MATHML', 5 ); /// @deprecated
define( 'MW_MATH_MATHJAX', 6 ); /// new in 1.19/1.20
define( 'MW_MATH_LATEXML', 7 ); /// new in 1.22
/**@}*/

/** For back-compat */
$wgUseTeX = true;

/** Location of the texvc binary */
$wgTexvc = dirname( __FILE__ ) . '/math/texvc';
/** Location of the latexmlmath binary */
$wgLaTeXML = '/usr/local/bin/latexmlmath';
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
 * Experimental option to use MathJax library to do client-side math rendering
 * when JavaScript is available. In supporting browsers this makes nice output
 * that's scalable for zooming, printing, and high-resolution displays.
 *
 * Not guaranteed to be stable at this time.
 */
$wgUseMathJax = true;

/**
 * Use of LaTeXML for details see
 * <http://latexml.mathweb.org/help>
 *
 * If you want or need to run your own server, follow these installation
 * instructions and override $wgLaTeXMLUrl:
 * <https://svn.mathweb.org/repos/LaTeXML/branches/arXMLiv/INSTALL>
 *
 * If you expect heavy load you can specify multiple servers. In that case one
 * server is randomly chosen for each rendering process. Specify the list of
 * servers in an array e.g $wgLaTeXMLUrl = array ( 'http://latexml.example.com/convert',
 * 'http://latexml2.example.com/convert');
 */
$wgLaTeXMLUrl = 'http://latexml.mathweb.org/convert';

/**
 * Allows to use LaTeXML as renderer for mathematical equation.
 */
$wgUseLaTeXML = true;

/**
 * The timeout for the HTTP-Request sent to the LaTeXML to render an equation,
 * in seconds.
 */
$wgLaTeXMLTimeout = 240;
/**
 * Option to disable the tex filter. If set to true any LaTeX espression is parsed
 * this can be a potential security risk. If set to false only a subset of the tex
 * commands is allowed. See the wikipedia page Help:Math for details.
 */
$wgDisableTexFilter = true;

/**
 * Setting for the LaTeXML renderer.
 * See http://dlmf.nist.gov/LaTeXML/manual/commands/latexmlpost.xhtml for details.
 */
$wgDefaultLaTeXMLSetting = 'format=xhtml&whatsin=math&whatsout=math&pmml&cmml&nodefaultresources&preload=LaTeX.pool&preload=article.cls&preload=amsmath.sty&preload=amsthm.sty&preload=amstext.sty&preload=amssymb.sty&preload=eucal.sty&preload=[dvipsnames]xcolor.sty&preload=url.sty&preload=hyperref.sty&preload=[ids]latexml.sty&preload=texvc';
////////// end of config settings.

$wgDefaultUserOptions['math'] = MW_MATH_LATEXML;

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
$wgAutoloadClasses['MathTexvc'] = $dir . 'MathTexvc.php';
$wgAutoloadClasses['MathSource'] = $dir . 'MathSource.php';
$wgAutoloadClasses['MathMathJax'] = $dir . 'MathMathJax.php';
$wgAutoloadClasses['MathLaTeXML'] = $dir . 'MathLaTeXML.php';
$wgAutoloadClasses['MathLaTeXMLImages'] = $dir . 'MathLaTeXMLImages.php';

$wgExtensionMessagesFiles['Math'] = $dir . 'Math.i18n.php';

$wgParserTestFiles[] = $dir . 'mathParserTests.txt';

$moduleTemplate = array(
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'Math/modules',
);

$wgResourceModules += array(
        'ext.math.mathjax' => array(
                'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked',
                'remoteExtPath' => 'Math/modules/MathJax/unpacked',
                'scripts' => 'MathJax.js'
        ),
        'ext.math.mathjax.enabler' => array(
                'localBasePath' => dirname( __FILE__ ) . '/modules',
                'remoteExtPath' => 'Math/modules',
                'scripts' => 'ext.math.mathjax.enabler.js'
        ),
        'ext.math.mathjax.localization' => array(
                'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/localization',
                'remoteExtPath' => 'Math/modules/MathJax/unpacked/localization',
                'languageScripts' => array(
			'en' => array (), // Empty since an English fallback is always specified in MathJax's code when a string is used.
			'de' => array ('de/de.js', 'de/HelpDialog.js', 'de/MathMenu.js', 'de/TeX.js', 'de/FontWarnings.js', 'de/HTML-CSS.js', 'de/MathML.js'),
			'fr' => array ('fr/fr.js', 'fr/HelpDialog.js', 'fr/MathMenu.js', 'fr/TeX.js', 'fr/FontWarnings.js', 'fr/HTML-CSS.js', 'fr/MathML.js'),
			'it' => array ('it/it.js', 'it/HelpDialog.js', 'it/MathMenu.js', 'it/TeX.js', 'it/FontWarnings.js', 'it/HTML-CSS.js', 'it/MathML.js'),
			'pl' => array ('pl/pl.js', 'pl/HelpDialog.js', 'pl/MathMenu.js', 'pl/TeX.js', 'pl/FontWarnings.js', 'pl/HTML-CSS.js', 'pl/MathML.js'),
			'pt-br' => array ('pt-br/pt-br.js', 'pt-br/HelpDialog.js', 'pt-br/MathMenu.js', 'pt-br/TeX.js', 'pt-br/FontWarnings.js', 'pt-br/HTML-CSS.js', 'pt-br/MathML.js'),
		),
		'dependencies' => 'ext.math.mathjax'
	),
	'ext.math.mathjax.jax.config' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax',
		'scripts' => array('input/TeX/config.js','input/MathML/config.js','output/HTML-CSS/config.js','output/NativeMML/config.js','output/SVG/config.js'),
		'dependencies' => 'ext.math.mathjax'
	),
	'ext.math.mathjax.ui' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/extensions',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/extensions',
		'scripts' => array('MathEvents.js','MathZoom.js','MathMenu.js','toMathML.js'),
		'dependencies' => 'ext.math.mathjax'
	),
	'ext.math.mathjax.jax.element.mml' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/element/mml',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/element/mml',
		'scripts' => array('jax.js'),
		'dependencies' => 'ext.math.mathjax'
	),
	'ext.math.mathjax.jax.input.TeX' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked',
		'scripts' => array('extensions/wiki2jax.js','jax/input/TeX/jax.js','extensions/TeX/noUndefined.js','extensions/TeX/AMSmath.js','extensions/TeX/AMSsymbols.js','extensions/TeX/texvc.js'),
		'dependencies' => array('ext.math.mathjax.jax.config', 'ext.math.mathjax.jax.element.mml')
	),
	'ext.math.mathjax.jax.output.HTML-CSS' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/HTML-CSS',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/HTML-CSS',
		'scripts' => array('jax.js','autoload/multiline.js','autoload/mtable.js'),
		'dependencies' => array('ext.math.mathjax.jax.config', 'ext.math.mathjax.jax.element.mml', 'ext.math.mathjax.ui')
	),
	'ext.math.mathjax.all' => array(
                                        'dependencies' => array('ext.math.mathjax.localization','ext.math.mathjax.jax.input.TeX','ext.math.mathjax.ui')
        )
	,'ext.math.mathjax.jax.input.MathML' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked')
);
