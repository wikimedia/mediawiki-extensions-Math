<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Lr extends TexNode {

	public function __construct(
		private readonly string $left,
		private readonly string $right,
		private readonly TexArray $arg,
	) {
		parent::__construct( $left, $right, $arg );
	}

	public function getLeft(): string {
		return $this->left;
	}

	public function getRight(): string {
		return $this->right;
	}

	public function getArg(): TexArray {
		return $this->arg;
	}

	public function renderArg( RenderContext $ctx ): MMLbase {
		return $this->renderChild( $this->arg, $ctx );
	}

	/** @inheritDoc */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/** @inheritDoc */
	public function render() {
		return '\\left' . $this->left . $this->arg->render() . '\\right' . $this->right;
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		$bm = new BaseMethods();
		$left = $bm->checkAndParseDelimiter( $this->left, $this, [], null, false,
			TexClass::OPEN );
		if ( $left->isEmpty() ) {
			$left = new MMLmo( TexClass::OPEN, [], $this->right );
		}
		$right = $bm->checkAndParseDelimiter( $this->right, $this, [], null, false,
			TexClass::CLOSE );
		if ( $right->isEmpty() ) {
			$right = new MMLmo( TexClass::CLOSE, [], $this->right );
		}
		$inner = $this->renderArg( $ctx );
		return new MMLmrow( TexClass::INNER, [], $left, $inner, $right );
	}

	/** @inheritDoc */
	public function argContext( TexNode $arg, RenderContext $ctx, array $links = [], ?array $colors = null
	): RenderContext {
		// Only the group directly before \right, not groups nested in it, see TexArray::checkForDots
		return $ctx->forArgument( $ctx->style, $ctx->font, colorDefinitions: $colors, links: $links,
			beforeRight: true );
	}

	/** @inheritDoc */
	public function containsFunc( $target, $args = null ) {
		if ( $args == null ) {
			$args = [ '\\left', '\\right', $this->arg ];
		}
		return parent::containsFunc( $target, $args );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg ];
		}
		return parent::extractIdentifiers( $args );
	}

}
