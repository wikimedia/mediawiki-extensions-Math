<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Big extends TexNode {

	public function __construct(
		private readonly string $fname,
		private readonly string $arg,
	) {
		parent::__construct( $fname, $arg );
	}

	public function getFname(): string {
		return $this->fname;
	}

	public function getArg(): string {
		return $this->arg;
	}

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		return '{' . $this->fname . ' ' . $this->arg . '}';
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		return $this->parseToMML( $this->fname, $ctx );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		return [];
	}

}
