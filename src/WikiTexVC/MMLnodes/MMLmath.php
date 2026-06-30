<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

use MediaWiki\Html\Html;

class MMLmath extends MMLbase {

	/** @inheritDoc */
	public function __construct( string $texclass = "", array $attributes = [], ...$children ) {
		$attributes["xmlns"] = "http://www.w3.org/1998/Math/MathML";
		parent::__construct( "math", $texclass, $attributes, ...$children );
	}

	/**
	 * Wrap text content in a <math> element
	 * @param string $content The raw HTML contents of the math element: *not* escaped!
	 * @return string HTML string of the math element
	 */
	public function wrapRawFragment( string $content ): string {
		return HTML::rawElement( $this->getName(), $this->getAttributes(), $content );
	}
}
