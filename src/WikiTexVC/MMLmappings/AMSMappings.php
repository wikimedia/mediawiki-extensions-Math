<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Lengths\MathSpace;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Align;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

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

	private const ALL = [
		"amsmacros" => self::AMSMACROS
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
		$rendering = TexUtil::getInstance()->environment_rendering( trim( $key ) );
		return $rendering !== false ? $rendering : null;
	}
}
