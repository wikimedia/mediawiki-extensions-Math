<?php
/**
 * Internationalization file for the Math extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English */
$messages['en'] = array(
	// Edit toolbar stuff shown on ?action=edit (example text & tooltip)
	'math_sample' => 'Insert formula here',
	'math_tip' => 'Mathematical formula (LaTeX)',

	// Header on Special:Preferences (or something)
	'prefs-math' => 'Math',

	// Math options
	'mw_math_png' => 'Always render PNG',
	'mw_math_simple' => 'HTML if very simple or else PNG',
	'mw_math_html' => 'HTML if possible or else PNG',
	'mw_math_source' => 'Leave it as TeX (for text browsers)',
	'mw_math_modern' => 'Recommended for modern browsers',
	'mw_math_mathml' => 'MathML if possible (experimental)',

	// Math errors
	'math_failure' => 'Failed to parse',
	'math_unknown_error' => 'unknown error',
	'math_unknown_function' => 'unknown function',
	'math_lexing_error' => 'lexing error',
	'math_syntax_error' => 'syntax error',
	'math_image_error' => 'PNG conversion failed; check for correct installation of latex and dvipng (or dvips + gs + convert)',
	'math_bad_tmpdir' => 'Cannot write to or create math temp directory',
	'math_bad_output' => 'Cannot write to or create math output directory',
	'math_notexvc' => 'Missing texvc executable; please see math/README to configure.',
);