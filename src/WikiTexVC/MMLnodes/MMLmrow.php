<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;

/**
 * Presentation MathML 3 Element
 * name: "mrow"
 * description: "group any number of sub-expressions horizontally"
 * category: "General Layout Schemata"
 */
class MMLmrow extends MMLbase {

	/** @inheritDoc */
	public function __construct( string $texclass = TexClass::ORD, array $attributes = [], ...$children ) {
		parent::__construct( "mrow", $texclass, $attributes, ...$children );
	}
}
