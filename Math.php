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
$wgMathMathMLUrl = 'http://localhost:8010';
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
$wgHooks['PageRenderingHash'][] = 'MathHooks::onPageRenderingHash';

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

// Conditional rendering of the MathML output.
$wgResourceModules = array(
	// Module to choose the best rendering option.
	'ext.math.selector' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'Math/modules',
                'scripts' => 'ext.math.selector.js'
	),
        // Standard rendering with MathML.
	'ext.math.mathml' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'Math/modules',
		'styles' => 'ext.math.mathml.css'
	),
	// Fallback rendering with SVG.
	'ext.math.svg' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'Math/modules',
		'styles' => 'ext.math.svg.css'
	),
	// Web Fonts for native MathML.
	'ext.math.fonts' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'Math/modules',
		'styles' => 'ext.math.fonts.css'
	)
);

// MathJax module
// If you modify these arrays, update ext.math.mathjax.enabler.js to ensure
// that getModuleNameFromFile knows how to map files to MediaWiki modules.
$wgResourceModules += array(
	// This enables MathJax.
	'ext.math.mathjax.enabler' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules',
		'remoteExtPath' => 'Math/modules',
		'scripts' => 'ext.math.mathjax.enabler.js'
	),
	// Main MathJax file
	'ext.math.mathjax.mathjax' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked',
		'scripts' => 'MathJax.js'
	),

	// Localization data for the current language
	'ext.math.mathjax.localization' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/localization',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/localization',
		'languageScripts' => array(
			// The localization data for 'en' are actually never used since an English fallback is always specified in MathJax's code when a string is used.
			'br' => array ('br/br.js', 'br/HelpDialog.js', 'br/MathMenu.js', 'br/TeX.js', 'br/FontWarnings.js', 'br/HTML-CSS.js', 'br/MathML.js'),
			'cdo' => array ('cdo/cdo.js', 'cdo/HelpDialog.js', 'cdo/MathMenu.js', 'cdo/TeX.js', 'cdo/FontWarnings.js', 'cdo/HTML-CSS.js', 'cdo/MathML.js'),
			'cs' => array ('cs/cs.js', 'cs/HelpDialog.js', 'cs/MathMenu.js', 'cs/TeX.js', 'cs/FontWarnings.js', 'cs/HTML-CSS.js', 'cs/MathML.js'),
			'da' => array ('da/da.js', 'da/HelpDialog.js', 'da/MathMenu.js', 'da/TeX.js', 'da/FontWarnings.js', 'da/HTML-CSS.js', 'da/MathML.js'),
			'de' => array ('de/de.js', 'de/HelpDialog.js', 'de/MathMenu.js', 'de/TeX.js', 'de/FontWarnings.js', 'de/HTML-CSS.js', 'de/MathML.js'),
			'eo' => array ('eo/eo.js', 'eo/HelpDialog.js', 'eo/MathMenu.js', 'eo/TeX.js', 'eo/FontWarnings.js', 'eo/HTML-CSS.js', 'eo/MathML.js'),
			'es' => array ('es/es.js', 'es/HelpDialog.js', 'es/MathMenu.js', 'es/TeX.js', 'es/FontWarnings.js', 'es/HTML-CSS.js', 'es/MathML.js'),
			'fa' => array ('fa/fa.js', 'fa/HelpDialog.js', 'fa/MathMenu.js', 'fa/TeX.js', 'fa/FontWarnings.js', 'fa/HTML-CSS.js', 'fa/MathML.js'),
			'fi' => array ('fi/fi.js', 'fi/HelpDialog.js', 'fi/MathMenu.js', 'fi/TeX.js', 'fi/FontWarnings.js', 'fi/HTML-CSS.js', 'fi/MathML.js'),
			'fr' => array ('fr/fr.js', 'fr/HelpDialog.js', 'fr/MathMenu.js', 'fr/TeX.js', 'fr/FontWarnings.js', 'fr/HTML-CSS.js', 'fr/MathML.js'),
			'gl' => array ('gl/gl.js', 'gl/HelpDialog.js', 'gl/MathMenu.js', 'gl/TeX.js', 'gl/FontWarnings.js', 'gl/HTML-CSS.js', 'gl/MathML.js'),
			'he' => array ('he/he.js', 'he/HelpDialog.js', 'he/MathMenu.js', 'he/TeX.js', 'he/FontWarnings.js', 'he/HTML-CSS.js', 'he/MathML.js'),
			'ia' => array ('ia/ia.js', 'ia/HelpDialog.js', 'ia/MathMenu.js', 'ia/TeX.js', 'ia/FontWarnings.js', 'ia/HTML-CSS.js', 'ia/MathML.js'),
			'it' => array ('it/it.js', 'it/HelpDialog.js', 'it/MathMenu.js', 'it/TeX.js', 'it/FontWarnings.js', 'it/HTML-CSS.js', 'it/MathML.js'),
			'ja' => array ('ja/ja.js', 'ja/HelpDialog.js', 'ja/MathMenu.js', 'ja/TeX.js', 'ja/FontWarnings.js', 'ja/HTML-CSS.js', 'ja/MathML.js'),
			'ko' => array ('ko/ko.js', 'ko/HelpDialog.js', 'ko/MathMenu.js', 'ko/TeX.js', 'ko/FontWarnings.js', 'ko/HTML-CSS.js', 'ko/MathML.js'),
			'lb' => array ('lb/lb.js', 'lb/HelpDialog.js', 'lb/MathMenu.js', 'lb/TeX.js', 'lb/FontWarnings.js', 'lb/HTML-CSS.js', 'lb/MathML.js'),
			'mk' => array ('mk/mk.js', 'mk/HelpDialog.js', 'mk/MathMenu.js', 'mk/TeX.js', 'mk/FontWarnings.js', 'mk/HTML-CSS.js', 'mk/MathML.js'),
			'nl' => array ('nl/nl.js', 'nl/HelpDialog.js', 'nl/MathMenu.js', 'nl/TeX.js', 'nl/FontWarnings.js', 'nl/HTML-CSS.js', 'nl/MathML.js'),
			'oc' => array ('oc/oc.js', 'oc/HelpDialog.js', 'oc/MathMenu.js', 'oc/TeX.js', 'oc/FontWarnings.js', 'oc/HTML-CSS.js', 'oc/MathML.js'),
			'pl' => array ('pl/pl.js', 'pl/HelpDialog.js', 'pl/MathMenu.js', 'pl/TeX.js', 'pl/FontWarnings.js', 'pl/HTML-CSS.js', 'pl/MathML.js'),
			'pt' => array ('pt/pt.js', 'pt/HelpDialog.js', 'pt/MathMenu.js', 'pt/TeX.js', 'pt/FontWarnings.js', 'pt/HTML-CSS.js', 'pt/MathML.js'),
			'pt-br' => array ('pt-br/pt-br.js', 'pt-br/HelpDialog.js', 'pt-br/MathMenu.js', 'pt-br/TeX.js', 'pt-br/FontWarnings.js', 'pt-br/HTML-CSS.js', 'pt-br/MathML.js'),
			'ru' => array ('ru/ru.js', 'ru/HelpDialog.js', 'ru/MathMenu.js', 'ru/TeX.js', 'ru/FontWarnings.js', 'ru/HTML-CSS.js', 'ru/MathML.js'),
			'sl' => array ('sl/sl.js', 'sl/HelpDialog.js', 'sl/MathMenu.js', 'sl/TeX.js', 'sl/FontWarnings.js', 'sl/HTML-CSS.js', 'sl/MathML.js'),
			'sv' => array ('sv/sv.js', 'sv/HelpDialog.js', 'sv/MathMenu.js', 'sv/TeX.js', 'sv/FontWarnings.js', 'sv/HTML-CSS.js', 'sv/MathML.js'),
			'tr' => array ('tr/tr.js', 'tr/HelpDialog.js', 'tr/MathMenu.js', 'tr/TeX.js', 'tr/FontWarnings.js', 'tr/HTML-CSS.js', 'tr/MathML.js'),
			'uk' => array ('uk/uk.js', 'uk/HelpDialog.js', 'uk/MathMenu.js', 'uk/TeX.js', 'uk/FontWarnings.js', 'uk/HTML-CSS.js', 'uk/MathML.js'),
			'zh-hans' => array ('zh-hans/zh-hans.js', 'zh-hans/HelpDialog.js', 'zh-hans/MathMenu.js', 'zh-hans/TeX.js', 'zh-hans/FontWarnings.js', 'zh-hans/HTML-CSS.js', 'zh-hans/MathML.js')
		),
		'dependencies' => 'ext.math.mathjax.mathjax'
	),

	// Configuration files for the MathJax input/output processors
	'ext.math.mathjax.jax.config' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax',
		'scripts' => array('input/TeX/config.js','input/MathML/config.js','output/HTML-CSS/config.js','output/NativeMML/config.js','output/SVG/config.js'),
		'dependencies' => 'ext.math.mathjax.mathjax'
	),

	// MathJax Extensions used in MediaWiki
	//
	// Note that these extensions wait to receive 'ready' signals from their
	// dependencies. Hence we only specify 'ext.math.mathjax.mathjax' here so that
	// we can load them in MathJax.Hub.Startup.Extensions.
	'ext.math.mathjax.extensions.ui' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/extensions',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/extensions',
		'scripts' => array('MathEvents.js','MathZoom.js','MathMenu.js','toMathML.js'),
		'dependencies' => 'ext.math.mathjax.mathjax'
	),
	'ext.math.mathjax.extensions.TeX' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/extensions',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/extensions',
		'scripts' => array('wiki2jax.js','TeX/noUndefined.js','TeX/AMSmath.js','TeX/AMSsymbols.js','TeX/boldsymbol.js','TeX/texvc.js'),
		'dependencies' => array('ext.math.mathjax.mathjax')
	),
	'ext.math.mathjax.extensions' => array(
		'dependencies' => array('ext.math.mathjax.extensions.ui','ext.math.mathjax.extensions.TeX')
	),

	// MathJax module for representing MathML elements
	'ext.math.mathjax.jax.element.mml.optable' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/element/mml/optable',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/element/mml/optable',
		'scripts' => array('Arrows.js','BasicLatin.js','CombDiacritMarks.js','CombDiactForSymbols.js','Dingbats.js','GeneralPunctuation.js','GeometricShapes.js','GreekAndCoptic.js','Latin1Supplement.js','LetterlikeSymbols.js','MathOperators.js','MiscMathSymbolsA.js','MiscMathSymbolsB.js','MiscSymbolsAndArrows.js','MiscTechnical.js','SpacingModLetters.js','SupplementalArrowsA.js','SupplementalArrowsB.js','SuppMathOperators.js'),
		'dependencies' => array('ext.math.mathjax.jax.element.mml')
	),
	'ext.math.mathjax.jax.element.mml' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/element/mml',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/element/mml',
		'scripts' => array('jax.js'),
		'dependencies' => 'ext.math.mathjax.mathjax'
	),

	// MathJax MathML input processor
	//
	// Note that upstream has an entities/ directory with Javascript files
	// defining entity names of http://www.w3.org/TR/xml-entity-names/
	// We don't use these files because these entities are now well
	// supported by modern HTML5 rendering engines anyway.
	'ext.math.mathjax.jax.input.MathML' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/input/MathML',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/input/MathML',
		'scripts' => array('jax.js'),
		'dependencies' => array('ext.math.mathjax.jax.config', 'ext.math.mathjax.jax.element.mml')
	),

	// MathJax TeX input processor
	'ext.math.mathjax.jax.input.TeX' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/input/TeX',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/input/TeX',
		'scripts' => array('jax.js'),
		'dependencies' => array('ext.math.mathjax.jax.config', 'ext.math.mathjax.jax.element.mml')
	),

	// MathJax NativeMML output processor
	'ext.math.mathjax.jax.output.NativeMML' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/NativeMML',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/NativeMML',
		'scripts' => array('jax.js'),
		'dependencies' => array('ext.math.mathjax.jax.config', 'ext.math.mathjax.jax.element.mml')
	),

	// MathJax HTML-CSS output processor
	// Note: at the moment, we use neither image fonts nor STIX/STIX-Web/Asana/GyrePagella/GyreTermes/NeoEuler/LatinModern fonts.
	'ext.math.mathjax.jax.output.HTML-CSS.autoload' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/HTML-CSS/autoload',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/HTML-CSS/autoload',
		'scripts' => array('annotation-xml.js','maction.js','menclose.js','mglyph.js','mmultiscripts.js','ms.js','mtable.js','multiline.js'),
		'dependencies' => array('ext.math.mathjax.jax.output.HTML-CSS')
	),
	'ext.math.mathjax.jax.output.HTML-CSS' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/HTML-CSS',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/HTML-CSS',
		'scripts' => array('jax.js'),
		'dependencies' => array('ext.math.mathjax.jax.config', 'ext.math.mathjax.jax.element.mml')
	),
	'ext.math.mathjax.jax.output.HTML-CSS.fonts.TeX.fontdata' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/HTML-CSS/fonts/TeX',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/HTML-CSS/fonts/TeX',
		'scripts' => array('fontdata.js','fontdata-extra.js'),
		'dependencies' => array('ext.math.mathjax.jax.output.HTML-CSS')
	),

	// MathJax SVG output processor
	'ext.math.mathjax.jax.output.SVG.autoload' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/SVG/autoload',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/SVG/autoload',
		'scripts' => array('annotation-xml.js','maction.js','menclose.js','mglyph.js','mmultiscripts.js','ms.js','mtable.js','multiline.js'),
		'dependencies' => array('ext.math.mathjax.jax.output.SVG')
	),
	'ext.math.mathjax.jax.output.SVG' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/SVG',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/SVG',
		'scripts' => array('jax.js'),
		'dependencies' => array('ext.math.mathjax.jax.config', 'ext.math.mathjax.jax.element.mml')
	),
	'ext.math.mathjax.jax.output.SVG.fonts.TeX.fontdata' => array(
		'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/SVG/fonts/TeX',
		'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/SVG/fonts/TeX',
		'scripts' => array('fontdata.js','fontdata-extra.js'),
		'dependencies' => array('ext.math.mathjax.jax.output.SVG')
	)
);

// MathJax TeX Fonts
// - The two sets for HTML-CSS and SVG are slightly different, so we can't really use a foreach loop.
// - the Main.js files must be executed before the other files (the former define the MathJax.OutputJax[*].FONTDATA.FONTS[*] object while the latter extend that object). Hence we create separate *MainJS modules for them.
$moduleTemplateHTMLCSS = array(
	'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/HTML-CSS/fonts/TeX',
	'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/HTML-CSS/fonts/TeX',
	'dependencies' => array('ext.math.mathjax.jax.output.HTML-CSS.fonts.TeX.fontdata')
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.HTML-CSS.fonts.TeX.MainJS' => array(
		'scripts' => array('Size1/Regular/Main.js','Size2/Regular/Main.js','Size3/Regular/Main.js','Size4/Regular/Main.js','Main/Bold/Main.js','Main/Italic/Main.js','Main/Regular/Main.js','AMS/Regular/Main.js','Caligraphic/Bold/Main.js','Caligraphic/Regular/Main.js','Fraktur/Bold/Main.js','Fraktur/Regular/Main.js','Greek/BoldItalic/Main.js','Greek/Bold/Main.js','Greek/Italic/Main.js','Greek/Regular/Main.js','Math/BoldItalic/Main.js','Math/Italic/Main.js','SansSerif/Bold/Main.js','SansSerif/Italic/Main.js','SansSerif/Regular/Main.js','Script/Regular/Main.js','Typewriter/Regular/Main.js','WinChrome/Regular/Main.js','WinIE6/Regular/Main.js')
	) + $moduleTemplateHTMLCSS
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.HTML-CSS.fonts.TeX.Main' => array(
		'scripts' => array('Main/Bold/Arrows.js','Main/Bold/CombDiacritMarks.js','Main/Bold/CombDiactForSymbols.js','Main/Bold/GeneralPunctuation.js','Main/Bold/GeometricShapes.js','Main/Bold/Latin1Supplement.js','Main/Bold/LatinExtendedA.js','Main/Bold/LatinExtendedB.js','Main/Bold/LetterlikeSymbols.js','Main/Bold/MathOperators.js','Main/Bold/MiscMathSymbolsA.js','Main/Bold/MiscSymbols.js','Main/Bold/MiscTechnical.js','Main/Bold/SpacingModLetters.js','Main/Bold/SupplementalArrowsA.js','Main/Bold/SuppMathOperators.js','Main/Italic/CombDiacritMarks.js','Main/Italic/GeneralPunctuation.js','Main/Italic/Latin1Supplement.js','Main/Italic/LetterlikeSymbols.js','Main/Regular/CombDiacritMarks.js','Main/Regular/GeometricShapes.js','Main/Regular/MiscSymbols.js','Main/Regular/SpacingModLetters.js')
	) + $moduleTemplateHTMLCSS
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.HTML-CSS.fonts.TeX.AMS' => array(
		'scripts' => array('AMS/Regular/Arrows.js','AMS/Regular/BBBold.js','AMS/Regular/BoxDrawing.js','AMS/Regular/CombDiacritMarks.js','AMS/Regular/Dingbats.js','AMS/Regular/EnclosedAlphanum.js','AMS/Regular/GeneralPunctuation.js','AMS/Regular/GeometricShapes.js','AMS/Regular/GreekAndCoptic.js','AMS/Regular/Latin1Supplement.js','AMS/Regular/LatinExtendedA.js','AMS/Regular/LetterlikeSymbols.js','AMS/Regular/MathOperators.js','AMS/Regular/MiscMathSymbolsB.js','AMS/Regular/MiscSymbols.js','AMS/Regular/MiscTechnical.js','AMS/Regular/PUA.js','AMS/Regular/SpacingModLetters.js','AMS/Regular/SuppMathOperators.js')
	) + $moduleTemplateHTMLCSS
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.HTML-CSS.fonts.TeX.Extra' => array(
		'scripts' => array('Fraktur/Bold/BasicLatin.js','Fraktur/Bold/Other.js','Fraktur/Bold/PUA.js','Fraktur/Regular/BasicLatin.js','Fraktur/Regular/Other.js','Fraktur/Regular/PUA.js','SansSerif/Bold/BasicLatin.js','SansSerif/Bold/CombDiacritMarks.js','SansSerif/Bold/Other.js','SansSerif/Italic/BasicLatin.js','SansSerif/Italic/CombDiacritMarks.js','SansSerif/Italic/Other.js','SansSerif/Regular/BasicLatin.js','SansSerif/Regular/CombDiacritMarks.js','SansSerif/Regular/Other.js','Script/Regular/BasicLatin.js','Script/Regular/Other.js','Typewriter/Regular/BasicLatin.js','Typewriter/Regular/CombDiacritMarks.js','Typewriter/Regular/Other.js','WinIE6/Regular/AMS.js','WinIE6/Regular/Bold.js')
	) + $moduleTemplateHTMLCSS
);
$moduleTemplateSVG = array(
	'localBasePath' => dirname( __FILE__ ) . '/modules/MathJax/unpacked/jax/output/SVG/fonts/TeX',
	'remoteExtPath' => 'Math/modules/MathJax/unpacked/jax/output/SVG/fonts/TeX',
	'dependencies' => array('ext.math.mathjax.jax.output.SVG.fonts.TeX.fontdata')
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.SVG.fonts.TeX.MainJS' => array(
		'scripts' => array('Size1/Regular/Main.js','Size2/Regular/Main.js','Size3/Regular/Main.js','Size4/Regular/Main.js','Main/Bold/Main.js','Main/Italic/Main.js','Main/Regular/Main.js','AMS/Regular/Main.js','Caligraphic/Bold/Main.js','Caligraphic/Regular/Main.js','Fraktur/Bold/Main.js','Fraktur/Regular/Main.js','Math/BoldItalic/Main.js','Math/Italic/Main.js','SansSerif/Bold/Main.js','SansSerif/Italic/Main.js','SansSerif/Regular/Main.js','Script/Regular/Main.js','Typewriter/Regular/Main.js')
	) + $moduleTemplateSVG
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.SVG.fonts.TeX.Main' => array(
		'scripts' => array('Main/Bold/Arrows.js','Main/Bold/BasicLatin.js','Main/Bold/CombDiacritMarks.js','Main/Bold/CombDiactForSymbols.js','Main/Bold/GeneralPunctuation.js','Main/Bold/GeometricShapes.js','Main/Bold/GreekAndCoptic.js','Main/Bold/Latin1Supplement.js','Main/Bold/LatinExtendedA.js','Main/Bold/LatinExtendedB.js','Main/Bold/LetterlikeSymbols.js','Main/Bold/MathOperators.js','Main/Bold/MiscMathSymbolsA.js','Main/Bold/MiscSymbols.js','Main/Bold/MiscTechnical.js','Main/Bold/SpacingModLetters.js','Main/Bold/SupplementalArrowsA.js','Main/Bold/SuppMathOperators.js','Main/Italic/BasicLatin.js','Main/Italic/CombDiacritMarks.js','Main/Italic/GeneralPunctuation.js','Main/Italic/GreekAndCoptic.js','Main/Italic/LatinExtendedA.js','Main/Italic/LatinExtendedB.js','Main/Italic/LetterlikeSymbols.js','Main/Italic/MathOperators.js','Main/Regular/BasicLatin.js','Main/Regular/CombDiacritMarks.js','Main/Regular/GeometricShapes.js','Main/Regular/GreekAndCoptic.js','Main/Regular/LatinExtendedA.js','Main/Regular/LatinExtendedB.js','Main/Regular/LetterlikeSymbols.js','Main/Regular/MathOperators.js','Main/Regular/MiscSymbols.js','Main/Regular/SpacingModLetters.js','Main/Regular/SuppMathOperators.js')
	) + $moduleTemplateSVG
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.SVG.fonts.TeX.AMS' => array(
		'scripts' => array('AMS/Regular/Arrows.js','AMS/Regular/BoxDrawing.js','AMS/Regular/CombDiacritMarks.js','AMS/Regular/Dingbats.js','AMS/Regular/EnclosedAlphanum.js','AMS/Regular/GeneralPunctuation.js','AMS/Regular/GeometricShapes.js','AMS/Regular/GreekAndCoptic.js','AMS/Regular/Latin1Supplement.js','AMS/Regular/LatinExtendedA.js','AMS/Regular/LetterlikeSymbols.js','AMS/Regular/MathOperators.js','AMS/Regular/MiscMathSymbolsB.js','AMS/Regular/MiscSymbols.js','AMS/Regular/MiscTechnical.js','AMS/Regular/PUA.js','AMS/Regular/SpacingModLetters.js','AMS/Regular/SuppMathOperators.js')
	) + $moduleTemplateSVG
);
$wgResourceModules += array(
	'ext.math.mathjax.jax.output.SVG.fonts.TeX.Extra' => array(
		'scripts' => array('Fraktur/Bold/BasicLatin.js','Fraktur/Bold/Other.js','Fraktur/Bold/PUA.js','Fraktur/Regular/BasicLatin.js','Fraktur/Regular/Other.js','Fraktur/Regular/PUA.js','SansSerif/Bold/BasicLatin.js','SansSerif/Bold/CombDiacritMarks.js','SansSerif/Bold/Other.js','SansSerif/Italic/BasicLatin.js','SansSerif/Italic/CombDiacritMarks.js','SansSerif/Italic/Other.js','SansSerif/Regular/BasicLatin.js','SansSerif/Regular/CombDiacritMarks.js','SansSerif/Regular/Other.js','Script/Regular/BasicLatin.js','Typewriter/Regular/BasicLatin.js','Typewriter/Regular/CombDiacritMarks.js','Typewriter/Regular/Other.js')
	) + $moduleTemplateSVG
);

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
