<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

/**
 * \ce or \pu with the argument converted to TeX by mhchem.
 */
class Mhchem extends Fun1 {

	/** @inheritDoc */
	public function render() {
		return $this->getArg()->render();
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		return $this->renderArg( $ctx );
	}

	/** @inheritDoc */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		return [];
	}
}
