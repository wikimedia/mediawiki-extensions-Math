<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Lengths\MathSpace;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

class UpdateTexutil extends Maintenance {

	private const LEGACY_CONCEPTS = [
		"displaystyle" => [ 'setStyle', 'D', true, 0 ],
		"textstyle" => [ 'setStyle', 'T', false, 0 ],
		"scriptstyle" => [ 'setStyle', 'S', false, 1 ],
		"scriptscriptstyle" => [ 'setStyle', 'SS', false, 2 ],
		"rm" => [ 'setFont', Variants::NORMAL ],
		"mit" => [ 'setFont', Variants::ITALIC ],
		"oldstyle" => [ 'setFont', Variants::OLDSTYLE ],
		"cal" => [ 'setFont', Variants::CALLIGRAPHIC ],
		"it" => [ 'setFont', Variants::MATHITALIC ],
		"bf" => [ 'setFont', Variants::BOLD ],
		"bbFont" => [ 'setFont', Variants::DOUBLESTRUCK ],
		"scr" => [ 'setFont', Variants::SCRIPT ],
		"frak" => [ 'setFont', Variants::FRAKTUR ],
		"sf" => [ 'setFont', Variants::SANSSERIF ],
		"tt" => [ 'setFont', Variants::MONOSPACE ],
		"mathrm" => [ 'mathFont', Variants::NORMAL ],
		"mathup" => [ 'mathFont', Variants::NORMAL ],
		"mathnormal" => [ 'mathFont', '' ],
		"mathbf" => [ 'mathFont', Variants::BOLD ],
		"mathbfup" => [ 'mathFont', Variants::BOLD ],
		"mathit" => [ 'mathFont', Variants::MATHITALIC ],
		"mathbfit" => [ 'mathFont', Variants::BOLDITALIC ],
		"mathbb" => [ 'mathFont', Variants::DOUBLESTRUCK ],
		"Bbb" => [ 'mathFont', Variants::DOUBLESTRUCK ],
		"mathfrak" => [ 'mathFont', Variants::FRAKTUR ],
		"mathbffrak" => [ 'mathFont', Variants::BOLDFRAKTUR ],
		"mathscr" => [ 'mathFont', Variants::SCRIPT ],
		"mathbfscr" => [ 'mathFont', Variants::BOLDSCRIPT ],
		"mathsf" => [ 'mathFont', Variants::SANSSERIF ],
		"mathsfup" => [ 'mathFont', Variants::SANSSERIF ],
		"mathbfsf" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"mathbfsfup" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"mathsfit" => [ 'mathFont', Variants::SANSSERIFITALIC ],
		"mathbfsfit" => [ 'mathFont', Variants::SANSSERIFBOLDITALIC ],
		"mathtt" => [ 'mathFont', Variants::MONOSPACE ],
		"mathcal" => [ 'mathFont', Variants::CALLIGRAPHIC ],
		"mathbfcal" => [ 'mathFont', Variants::BOLDCALLIGRAPHIC ],
		"emph" => [ 'mathFont', Variants::ITALIC ], // added this specific case, toggles roman/italic fonts
		"symrm" => [ 'mathFont', Variants::NORMAL ],
		"symup" => [ 'mathFont', Variants::NORMAL ],
		"symnormal" => [ 'mathFont', '' ],
		"symbf" => [ 'mathFont', Variants::BOLD ],
		"symbfup" => [ 'mathFont', Variants::BOLD ],
		"symit" => [ 'mathFont', Variants::ITALIC ],
		"symbfit" => [ 'mathFont', Variants::BOLDITALIC ],
		"symbb" => [ 'mathFont', Variants::DOUBLESTRUCK ],
		"symfrak" => [ 'mathFont', Variants::FRAKTUR ],
		"symbffrak" => [ 'mathFont', Variants::BOLDFRAKTUR ],
		"symscr" => [ 'mathFont', Variants::SCRIPT ],
		"symbfscr" => [ 'mathFont', Variants::BOLDSCRIPT ],
		"symsf" => [ 'mathFont', Variants::SANSSERIF ],
		"symsfup" => [ 'mathFont', Variants::SANSSERIF ],
		"symbfsf" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"symbfsfup" => [ 'mathFont', Variants::BOLDSANSSERIF ],
		"symsfit" => [ 'mathFont', Variants::SANSSERIFITALIC ],
		"symbfsfit" => [ 'mathFont', Variants::SANSSERIFBOLDITALIC ],
		"symtt" => [ 'mathFont', Variants::MONOSPACE ],
		"symcal" => [ 'mathFont', Variants::CALLIGRAPHIC ],
		"symbfcal" => [ 'mathFont', Variants::BOLDCALLIGRAPHIC ],
		"textrm" => [ 'hBox', null, Variants::NORMAL ],
		"textup" => [ 'hBox', null, Variants::NORMAL ],
		"textnormal" => [ 'hBox' ],
		"textit" => [ 'hBox', null, Variants::ITALIC ],
		"textbf" => [ 'hBox', null, Variants::BOLD ],
		"textsf" => [ 'hBox', null, Variants::SANSSERIF ],
		"texttt" => [ 'hBox', null, Variants::MONOSPACE ],
		"tiny" => [ 'SetSize', 0.5 ],
		"Tiny" => [ 'SetSize', 0.6 ],
		"scriptsize" => [ 'SetSize', 0.7 ],
		"small" => [ 'SetSize', 0.85 ],
		"normalsize" => [ 'SetSize', 1.0 ],
		"large" => [ 'SetSize', 1.2 ],
		"Large" => [ 'SetSize', 1.44 ],
		"LARGE" => [ 'SetSize', 1.73 ],
		"huge" => [ 'SetSize', 2.07 ],
		"Huge" => [ 'SetSize', 2.49 ],
		"arcsin" => 'namedFn',
		"arccos" => 'namedFn',
		"arctan" => 'namedFn',
		"arg" => 'namedFn',
		"cos" => 'namedFn',
		"cosh" => 'namedFn',
		"cot" => 'namedFn',
		"coth" => 'namedFn',
		"csc" => 'namedFn',
		"deg" => 'namedFn',
		"det" => 'namedOp',
		"dim" => 'namedFn',
		"exp" => 'namedFn',
		"gcd" => 'namedOp',
		"hom" => 'namedFn',
		"inf" => 'namedOp',
		"ker" => 'namedFn',
		"lg" => 'namedFn',
		"lim" => 'namedOp',
		"liminf" => [ 'namedOp', 'lim inf' ],
		"limsup" => [ 'namedOp', 'lim sup' ],
		"ln" => 'namedFn',
		"log" => 'namedFn',
		"max" => 'namedOp',
		"min" => 'namedOp',
		"Pr" => 'namedOp',
		"sec" => 'namedFn',
		"sin" => 'namedFn',
		"sinh" => 'namedFn',
		"sup" => 'namedOp',
		"tan" => 'namedFn',
		"tanh" => 'namedFn',
		"limits" => [ 'limits', 1 ],
		"nolimits" => [ 'limits', 0 ],
		"overline" => [ 'underOver', '203E' ],
		"underline" => [ 'underOver', '_', null, true ],
		"overarc" => [ 'underOver', '23DC', 1 ],
		"overbrace" => [ 'underOver', '23DE', 1 ],
		"underbrace" => [ 'underOver', '23DF', 1 ],
		"overparen" => [ 'underOver', '23DC' ],
		"underparen" => [ 'underOver', '23DD' ],
		"overrightarrow" => [ 'underOver', '2192' ],
		"underrightarrow" => [ 'underOver', '2192' ],
		"overleftarrow" => [ 'underOver', '2190' ],
		"underleftarrow" => [ 'underOver', '2190' ],
		"overleftrightarrow" => [ 'underOver', '2194' ],
		"underleftrightarrow" => [ 'underOver', '2194' ],
		"overset" => 'overset',
		"underset" => 'underset',
		"overunderset" => 'Overunderset',
		"stackrel" => [ 'macro', '\\mathrel{\\mathop{#2}\\limits^{#1}}', 2 ],
		"stackbin" => [ 'macro', '\\mathbin{\\mathop{#2}\\limits^{#1}}', 2 ],
		"over" => 'over',
		"overwithdelims" => 'over',
		"atop" => 'over',
		"atopwithdelims" => 'over',
		"above" => 'over',
		"abovewithdelims" => 'over',
		"brace" => [ 'over', '{', '}' ],
		"brack" => [ 'over', '[', ']' ],
		"choose" => [ 'over', '(', ')' ],
		"frac" => 'frac',
		"sqrt" => 'sqrt',
		"root" => 'Root',
		"uproot" => [ 'MoveRoot', 'upRoot' ],
		"leftroot" => [ 'MoveRoot', 'leftRoot' ],
		"left" => 'LeftRight',
		"right" => 'LeftRight',
		"middle" => 'LeftRight',
		"llap" => 'lap',
		"rlap" => 'lap',
		"raise" => 'raiseLower',
		"lower" => 'raiseLower',
		"moveleft" => 'MoveLeftRight',
		"moveright" => 'MoveLeftRight',
		',' => [ 'spacer', MathSpace::THINMATHSPACE ],
		"'" => [ 'spacer', MathSpace::MEDIUMMATHSPACE ],
		'>' => [ 'spacer', MathSpace::MEDIUMMATHSPACE ],
		';' => [ 'spacer', MathSpace::THICKMATHSPACE ],
		'!' => [ 'spacer', MathSpace::NEGATIVETHINMATHSPACE ],
		"enspace" => [ 'spacer', 0.5 ],
		"quad" => [ 'spacer', 1 ],
		"qquad" => [ 'spacer', 2 ],
		"thinspace" => [ 'spacer', MathSpace::THINMATHSPACE ],
		"negthinspace" => [ 'spacer', MathSpace::NEGATIVETHINMATHSPACE ],
		"hskip" => 'hskip',
		"hspace" => 'hskip',
		"kern" => 'hskip',
		"mskip" => 'hskip',
		"mspace" => 'hskip',
		"mkern" => 'hskip',
		"rule" => 'rule',
		"Rule" => [ 'Rule' ],
		"Space" => [ 'Rule', 'blank' ],
		"nonscript" => 'Nonscript',
		"big" => [ 'makeBig', TexClass::ORD, 0.85 ],
		"Big" => [ 'makeBig', TexClass::ORD, 1.15 ],
		"bigg" => [ 'makeBig', TexClass::ORD, 1.45 ],
		"Bigg" => [ 'makeBig', TexClass::ORD, 1.75 ],
		"bigl" => [ 'makeBig', TexClass::OPEN, 0.85 ],
		"Bigl" => [ 'makeBig', TexClass::OPEN, 1.15 ],
		"biggl" => [ 'makeBig', TexClass::OPEN, 1.45 ],
		"Biggl" => [ 'makeBig', TexClass::OPEN, 1.75 ],
		"bigr" => [ 'makeBig', TexClass::CLOSE, 0.85 ],
		"Bigr" => [ 'makeBig', TexClass::CLOSE, 1.15 ],
		"biggr" => [ 'makeBig', TexClass::CLOSE, 1.45 ],
		"Biggr" => [ 'makeBig', TexClass::CLOSE, 1.75 ],
		"bigm" => [ 'makeBig', TexClass::REL, 0.85 ],
		"Bigm" => [ 'makeBig', TexClass::REL, 1.15 ],
		"biggm" => [ 'makeBig', TexClass::REL, 1.45 ],
		"Biggm" => [ 'makeBig', TexClass::REL, 1.75 ],
		"mathord" => [ 'TeXAtom', TexClass::ORD ],
		"mathop" => [ 'TeXAtom', TexClass::OP ],
		"mathopen" => [ 'TeXAtom', TexClass::OPEN ],
		"mathclose" => [ 'TeXAtom', TexClass::CLOSE ],
		"mathbin" => [ 'TeXAtom', TexClass::BIN ],
		"mathrel" => [ 'TeXAtom', TexClass::REL ],
		"mathpunct" => [ 'TeXAtom', TexClass::PUNCT ],
		"mathinner" => [ 'TeXAtom', TexClass::INNER ],
		"vcenter" => [ 'TeXAtom', TexClass::VCENTER ],
		"buildrel" => 'BuildRel',
		"hbox" => [ 'hBox', 0 ],
		"text" => 'hBox',
		"mbox" => [ 'hBox', 0 ],
		"vbox" => [ 'vbox', 0 ], // added this here in addition
		"fbox" => 'FBox',
		"boxed" => [ 'macro', '\\fbox{$\\displaystyle{#1}$}', 1 ],
		"framebox" => 'FrameBox',
		"strut" => 'Strut',
		"mathstrut" => [ 'macro', '\\vphantom{(}' ],
		"phantom" => 'phantom',
		"vphantom" => [ 'phantom', 1, 0 ],
		"hphantom" => [ 'phantom', 0, 1 ],
		"smash" => 'smash',
		"acute" => [ 'accent', '00B4' ],
		"grave" => [ 'accent', '0060' ],
		"ddot" => [ 'accent', '00A8' ],
		"tilde" => [ 'accent', '007E' ],
		"bar" => [ 'accent', '00AF' ],
		"breve" => [ 'accent', '02D8' ],
		"check" => [ 'accent', '02C7' ],
		"hat" => [ 'accent', '005E' ],
		"vec" => [ 'accent', '2192' ],
		"dot" => [ 'accent', '02D9' ],
		"widetilde" => [ 'accent', '007E', 1 ],
		"widehat" => [ 'accent', '005E', 1 ],
		"matrix" => 'matrix',
		"array" => 'matrix',
		"pmatrix" => [ 'matrix', '(', ')' ],
		"cases" => [ 'matrix', '{', '', 'left left', null, '.1em', null,
			true ],
		"eqalign" => [ 'matrix', null, null, 'right left',
			"(0, lengths_js_1.em)(MathSpace::thickmathspace)", '.5em', 'D' ],
		"displaylines" => [ 'matrix', null, null, 'center', null, '.5em', 'D' ],
		"cr" => 'Cr',
		"" => 'crLaTeX',
		"newline" => [ 'crLaTeX', true ],
		"hline" => [ 'hline', 'solid' ],
		"hdashline" => [ 'hline', 'dashed' ],
		"eqalignno" => [ 'matrix', null, null, 'right left',
			"(0, lengths_js_1.em)(MathSpace::thickmathspace)", '.5em', 'D', null,
			'right' ],
		"leqalignno" => [ 'matrix', null, null, 'right left',
			"(0, lengths_js_1.em)(MathSpace::thickmathspace)", '.5em', 'D', null,
			'left' ],
		"hfill" => 'HFill',
		"hfil" => 'HFill',
		"hfilll" => 'HFill',
		"bmod" => [ 'macro', '\\mmlToken{mo}[lspace="0.2777777777777778em"' .
			// "0.2777777777777778em" is equivlent to thickmathspace T320910
			' rspace="0.2777777777777778em"]{mod}' ],
		"pmod" => [ 'macro', '\\pod{\\mmlToken{mi}{mod}\\kern 6mu #1}', 1 ],
		"mod" => [ 'macro', '\\mathchoice{\\kern18mu}{\\kern12mu}' .
			'{\\kern12mu}{\\kern12mu}\\mmlToken{mi}{mod}\\,\\,#1',
			1 ],
		"pod" => [ 'macro', '\\mathchoice{\\kern18mu}{\\kern8mu}' .
			'{\\kern8mu}{\\kern8mu}(#1)', 1 ],
		"iff" => [ 'macro', '\\;\\Longleftrightarrow\\;' ],
		"skew" => [ 'macro', '{{#2{#3\\mkern#1mu}\\mkern-#1mu}{}}', 3 ],
		"pmb" => [ 'macro', '\\rlap{#1}\\kern1px{#1}', 1 ],
		"TeX" => [ 'macro', 'T\\kern-.14em\\lower.5ex{E}\\kern-.115em X' ],
		"LaTeX" => [ 'macro', 'L\\kern-.325em\\raise.21em' .
			'{\\scriptstyle{A}}\\kern-.17em\\TeX' ],
		' ' => [ 'macro', '\\text{ }' ],
		"not" => 'not',
		"dots" => 'dots',
		"space" => 'Tilde',
		'\u00A0' => 'Tilde',
		"begin" => 'BeginEnd',
		"end" => 'BeginEnd',
		"label" => 'HandleLabel',
		"ref" => 'HandleRef',
		"nonumber" => 'HandleNoTag',
		"mathchoice" => 'mathChoice',
		"mmlToken" => 'MmlToken',
		"intent" => 'intent',
		"implies" => [ 'macro', '\\;\\Longrightarrow\\;' ],
	];

	public function execute() {
		$jsonFilePath = './src/WikiTexVC/texutil.json';

		$jsonContent = json_decode( file_get_contents( $jsonFilePath ), true );

		if ( $jsonContent === null ) {
			die( "Failed to decode texutil.json. Please check the file format.\n" );
		}

		foreach ( self::LEGACY_CONCEPTS as $key => $value ) {
			 $key = '\\' . $key;

			// Check how to handle the value type (string or array)
			// as we don't have strings in the current value of LEGACY_CONCEPTS,
			// phab complains, that we don't need this check. Thus, I commented it out.
			// if ( is_string( $value ) ) {
			//	$value = [ $value ];
			//}
			$value[0] = MMLutil::uc2xNotation( $value[0] );
			// Remove the texClass from the array
//			foreach ( $value as $k => $v ) {
//				if ( is_array( $v ) && isset( $v['texClass'] ) ) {
//					unset( $value[$k]['texClass'] );
//				}
//			}
			// Check if the entry already exists in texutil.json
			if ( isset( $jsonContent["$key"] ) ) {
				// Preserve existing attributes and only add or update the identifier
				$jsonContent["$key"]['callback'] = $value;
			} else {
				// Create a new entry if it doesn't exist
				$jsonContent["$key"] = [
					'callback' => $value
				];
			}

			// Sort the entry alphabetically
			ksort( $jsonContent["$key"] );
		}
		// Sort the entire file
		ksort( $jsonContent );
		$jsonString = json_encode( $jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		$jsonStringWithTabs = preg_replace_callback( '/^( +)/m', static function ( $matches ) {
				// Convert spaces to tabs (assuming 4 spaces per tab level)
				return str_repeat( "\t", strlen( $matches[1] ) / 4 );
		}, $jsonString ) . "\n";
		// prevent eslint error  Unnecessary escape character: \/  no-useless-escape
		$jsonStringWithTabs = str_replace( '\/', '/', $jsonStringWithTabs );

		file_put_contents( $jsonFilePath, $jsonStringWithTabs );

		echo "texutil.json successfully updated.\n";
	}
}
$maintClass = UpdateTexutil::class;
require_once RUN_MAINTENANCE_IF_MAIN;
