<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Fun2sq extends Fun2 {

	/** @inheritDoc */
	protected function argStyle( TexNode $arg, int $style ): int {
		// arg1 is the optional argument in brackets, see render()
		return $arg === $this->arg1
			? RenderContext::nextStyle( $style, self::macro( $this->fname )['optArgStyle'] )
			: self::macroArgStyle( $this->fname, 0, $style );
	}

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		return '{' . $this->fname . '[' . $this->arg1->render() . ']' . $this->arg2->inCurlies() . '}';
	}
}
