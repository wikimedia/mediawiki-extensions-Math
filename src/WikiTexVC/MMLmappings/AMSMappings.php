<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Lengths\MathSpace;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Align;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

/**
 * Based on AMSMappings.js in MML3
 * Only importing infix atm
 * Singleton
 *
 */
class AMSMappings {

	/** @var self|null */
	private static $instance = null;
	private const AMSMACROS = [
		"mathring" => [ 'accent', '02DA' ],
		"nobreakspace" => 'Tilde',
		"negmedspace" => [ 'spacer', MathSpace::NEGATIVEMEDIUMMATHSPACE ],
		"negthickspace" => [ 'spacer', MathSpace::NEGATIVETHICKMATHSPACE ],
		"idotsint" => [ 'MultiIntegral', '\\int\\cdots\\int' ],
		"dddot" => [ 'accent', '20DB' ],
		"ddddot" => [ 'accent', '20DC' ],
		"sideset" => 'SideSet',
		"boxed" => [ 'macro', '\\fbox{$\\displaystyle{#1}$}', 1 ],
		"tag" => 'HandleTag',
		"notag" => 'HandleNoTag',
		"eqref" => [ 'HandleRef', true ],
		"substack" => [ 'macro', '\\begin{subarray}{c}#1\\end{subarray}', 1 ],
		"injlim" => [ 'namedOp', 'inj lim' ],
		"projlim" => [ 'namedOp', 'proj lim' ],
		"varliminf" => [ 'macro', '\\mathop{\\underline{\\mmlToken{mi}{lim}}}' ],
		"varlimsup" => [ 'macro', '\\mathop{\\overline{\\mmlToken{mi}{lim}}}' ],
		// replaced underrightarrow here not supported
		"varinjlim" => [ 'macro', '\\mathop{\\xrightarrow{\\mmlToken{mi}{lim}}}' ],
		// replaced underleftarrow here not supported
		"varprojlim" => [ 'macro', '\\mathop{\\xleftarrow{\\mmlToken{mi}{lim}}}' ],
		"DeclareMathOperator" => 'HandleDeclareOp',
		"operatorname" => 'handleOperatorName',
		"genfrac" => 'genFrac',
		"frac" => [ 'genFrac', '', '', '', '' ],
		"tfrac" => [ 'genFrac', '', '', '', '1' ],
		"dfrac" => [ 'genFrac', '', '', '', '0' ],
		"binom" => [ 'genFrac', '(', ')', '0', '0' ],
		"tbinom" => [ 'genFrac', '(', ')', '0', '1' ],
		"dbinom" => [ 'genFrac', '(', ')', '0', '0' ],
		"cfrac" => 'cFrac',
		"shoveleft" => [ 'HandleShove', Align::LEFT ],
		"shoveright" => [ 'HandleShove', Align::RIGHT ],
		"xrightarrow" => [ 'xArrow', 0x2192, 5, 10 ],
		"xleftarrow" => [ 'xArrow', 0x2190, 10, 5 ]
		];

	private const AMSMATHENVIRONMENT = [
		'equation*' => [ 'Equation', null, false ],
		'eqnarray*' => [ 'EqnArray', null, false, true, 'rcl', null, '.5em' ],
		'align' => [ 'EqnArray', null, true, true, 'rl', 'ParseUtil_js_1.default.cols(0, 2)' ],
		'align*' => [ 'EqnArray', null, false, true, 'rl', "ParseUtil_js_1.default.cols(0, 2)" ],
		"multline" => [ 'Multline', null, true ],
		'multline*' => [ 'Multline', null, false ],
		"split" => [ 'EqnArray', null, false, false, 'rl', "ParseUtil_js_1.default.cols(0)" ],
		"gather" => [ 'EqnArray', null, true, true, 'c' ],
		'gather*' => [ 'EqnArray', null, false, true, 'c' ],
		"alignat" => [ 'alignAt', null, true, true ],
		'alignat*' => [ 'alignAt', null, false, true ],
		"alignedat" => [ 'alignAt', null, false, false ],
		"aligned" => [ 'amsEqnArray', null, null, null, 'rl', "ParseUtil_js_1.default.cols(0, 2)", '.5em', 'D' ],
		"gathered" => [ 'amsEqnArray', null, null, null, 'c', null, '.5em', 'D' ],
		"xalignat" => [ 'XalignAt', null, true, true ],
		'xalignat*' => [ 'XalignAt', null, false, true ],
		"xxalignat" => [ 'XalignAt', null, false, false ],
		"flalign" => [ 'FlalignArray', null, true, false, true, 'rlc', 'auto auto fit' ],
		'flalign*' => [ 'FlalignArray', null, false, false, true, 'rlc', 'auto auto fit' ],
		"subarray" => [ 'array', null, null, null, null, "ParseUtil_js_1.default.cols(0)", '0.1em', 'S', 1 ],
		"smallmatrix" => [ 'array', null, null, null, 'c', "ParseUtil_js_1.default.cols(1 / 3)",
			'.2em', 'S', 1 ],
		"matrix" => [ 'array', null, null, null, 'c' ],
		"pmatrix" => [ 'array', null, '(', ')', 'c' ],
		"bmatrix" => [ 'array', null, '[', ']', 'c' ],
		"Bmatrix" => [ 'array', null, '\\{', '\\}', 'c' ],
		"vmatrix" => [ 'array', null, '\\vert', '\\vert', 'c' ],
		"Vmatrix" => [ 'array', null, '\\Vert', '\\Vert', 'c' ],
		'cases' => [ 'matrix', '{', '', 'left left', null, '.1em', null, true ],
		'array' => [ 'matrix' ]
	];

	private const ALL = [
		"amsmacros" => self::AMSMACROS,
		"amsmathenvironment" => self::AMSMATHENVIRONMENT
	];

	private function __construct() {
		// Just an empty private constructor, for singleton pattern
	}

	public static function getAll(): array {
		return self::ALL;
	}

	public static function getInstance(): self {
		self::$instance ??= new AMSMappings();
		return self::$instance;
	}

	public static function getMacroByKey( string $key ) {
		return MMLutil::getMappingByKey( $key, self::AMSMACROS, false, true );
	}

	public static function getEnvironmentByKey( string $key ) {
		return MMLutil::getMappingByKey( $key, self::AMSMATHENVIRONMENT );
	}
}
