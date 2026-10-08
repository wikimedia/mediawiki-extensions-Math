<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mo"
 * description: "Operator, Fence, Separator or Accent"
 * category: "Token Elements"
 */
class MMLmo extends MMLleaf {

	/** Operators with the movablelimits property in the MathML Core operator dictionary */
	private const MOVABLE_LIMITS = [ '∏', '∐', '∑', '⋀', '⋁', '⋂', '⋃', '⨀', '⨁', '⨂', '⨃', '⨄', '⨅', '⨆',
		'⨇', '⨈', '⨉', '⨊', '⨝', '⨞', '⫼', '⫿' ];

	public function __construct( string $texclass = "", array $attributes = [], string $text = "" ) {
		// WikiTexVC places limits as TeX does. The dictionary default would move them
		// whenever the MathML style differs from TeX's, e.g. in mtable.
		// https://github.com/w3c/mathml-core/issues/314
		if ( !isset( $attributes['movablelimits'] ) &&
			in_array( html_entity_decode( trim( $text ) ), self::MOVABLE_LIMITS, true )
		) {
			$attributes = [ 'movablelimits' => 'false' ] + $attributes;
		}
		parent::__construct( "mo", $texclass, $attributes, $text );
	}
}
