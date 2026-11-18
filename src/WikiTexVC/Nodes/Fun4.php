<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Fun4 extends TexNode {

	public function __construct(
		protected readonly string $fname,
		protected readonly TexNode $arg1,
		protected readonly TexNode $arg2,
		protected readonly TexNode $arg3,
		protected readonly TexNode $arg4,
	) {
		parent::__construct( $fname, $arg1, $arg2, $arg3, $arg4 );
	}

	public function getFname(): string {
		return $this->fname;
	}

	public function getArg1(): TexNode {
		return $this->arg1;
	}

	public function getArg2(): TexNode {
		return $this->arg2;
	}

	public function getArg3(): TexNode {
		return $this->arg3;
	}

	public function getArg4(): TexNode {
		return $this->arg4;
	}

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		return '{' . $this->fname . ' ' .
			$this->arg1->inCurlies() .
			$this->arg2->inCurlies() .
			$this->arg3->inCurlies() .
			$this->arg4->inCurlies() .
			'}';
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		return $this->parseToMML( $this->fname, $ctx );
	}

	/** MathJax MathChoice: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=2281-2287 */
	protected function mathChoice( RenderContext $ctx ): MMLbase {
		// The arguments are the material for display, text, script and scriptscript style.
		// tex.web: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=14372-14377
		$choice = $ctx->isDisplayStyle() ? 1 : $ctx->getScriptLevel() + 2;
		// getArgs()[0] is \mathchoice itself
		return $this->renderChild( $this->getArgs()[$choice], $ctx );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg1, $this->arg2, $this->arg3, $this->arg4 ];
		}
		return parent::extractIdentifiers( $args );
	}

}
