<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Infix extends TexNode {

	public function __construct(
		private readonly string $op,
		private readonly TexArray $arg1,
		private readonly TexArray $arg2,
	) {
		parent::__construct( $op, $arg1, $arg2 );
	}

	/** @inheritDoc */
	protected function argStyle( TexNode $arg, int $style ): int {
		// @phan-suppress-next-line PhanTypeComparisonFromArray phan takes TexArray, an ArrayAccess, for an array
		return self::macroArgStyle( $this->op, $this->arg1 === $arg ? 0 : 1, $style );
	}

	public function getOp(): string {
		return $this->op;
	}

	public function getArg1(): TexArray {
		return $this->arg1;
	}

	public function getArg2(): TexArray {
		return $this->arg2;
	}

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		return '{' . $this->arg1->render() .
			' ' . $this->op . ' ' .
			$this->arg2->render() . '}';
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		return $this->parseToMML( $this->op, $ctx );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg1, $this->arg2 ];
		}

		return parent::extractIdentifiers( $args );
	}

}
