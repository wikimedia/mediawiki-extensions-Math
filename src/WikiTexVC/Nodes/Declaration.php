<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Declaration extends TexNode {

	public function __construct(
		private readonly string $fname,
		private readonly TexArray $arg,
	) {
		parent::__construct( $fname, $arg );
	}

	public function getFname(): string {
		return $this->fname;
	}

	public function getArg(): TexArray {
		return $this->arg;
	}

	public function renderArg( RenderContext $ctx ): MMLbase {
		return $this->renderChild( $this->arg, $ctx );
	}

	/** @inheritDoc */
	public function inCurlies() {
		// render() of a font declaration writes its own braces, that of a math style declaration does not
		return $this->setsMathStyle() ? '{' . $this->render() . '}' : $this->render();
	}

	/** @inheritDoc */
	public function render() {
		if ( $this->setsMathStyle() ) {
			// Math style declarations keep the form of the input, as when they were nullary macros
			return $this->fname . ' ' . $this->arg->render();
		}
		return '{' . $this->fname . ' ' . $this->arg->inCurlies() . '}';
	}

	/** True for \displaystyle and the other math styles, false for fonts like \rm */
	public function setsMathStyle(): bool {
		return self::macro( $this->fname )['style'] !== null;
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		if ( !$this->setsMathStyle() ) {
			return $this->parseToMML( $this->fname, $ctx );
		}
		$argCtx = $this->argContext( $this->arg, $ctx );
		$mstyle = MMLmstyle::newWithDisplayStyle( $argCtx->isDisplayStyle(), $argCtx->getScriptLevel() );
		$mstyle->addChild( $this->arg->toMMLTree( $argCtx ) );
		return $mstyle;
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg ];
		}

		$identifier = parent::extractIdentifiers( $args );
		return $identifier ? [ implode( '', $identifier ) ] : [];
	}

	/** @inheritDoc */
	public function extractSubscripts() {
		$f = $this->fname;
		// @see
		// http://tex.stackexchange.com/questions/98406/which-command-should-i-use-for-textual-subscripts-in-math-mode
		// cf https://phabricator.wikimedia.org/T56818 a is always RM
		// for f there are only four cases
		switch ( $f ) {
			case '\\rm':
				$f = '\\mathrm';
				break;
			case '\\it':
				$f = '\\mathit';
				break;
			case '\\cal':
				$f = '\\mathcal';
				break;
			case '\\bf':
				$f = '\\mathbf';
		}

		$x = $this->arg->extractSubscripts();
		if ( isset( $x[0] ) ) {
			return [ $f . '{' . $x . '}' ];
		}
		return parent::extractSubscripts();
	}

}
