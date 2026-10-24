<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mstyle"
 * description: "style change"
 * category: "General Layout Schemata"
 */
class MMLmstyle extends MMLbase {

	/** @inheritDoc */
	public function __construct( string $texclass = "", array $attributes = [], ...$children ) {
		parent::__construct( "mstyle", $texclass, $attributes, ...$children );
	}

	public static function newWithDisplayStyle( bool $displayStyle, int $scriptLevel ): self {
		return new self( "", [
			"displaystyle" => $displayStyle ? "true" : "false",
			"scriptlevel" => (string)$scriptLevel
		] );
	}
}
