<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mpadded"
 * description:  ?
 * category:  ?
 */
class MMLmpadded extends MMLbase {

	/** @inheritDoc */
	public function __construct( string $texclass = "", array $attributes = [], MMLbase ...$children ) {
		parent::__construct( "mpadded", $texclass, $attributes, ...$children );
	}
}
