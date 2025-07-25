<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

class Fun1nb extends Fun1 {

	/** @inheritDoc */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/** @inheritDoc */
	public function render() {
		return $this->fname . ' ' . $this->arg->inCurlies() . ' ';
	}

	/** @inheritDoc */
	public function toMMLTree( array $arguments = [], array &$state = [] ) {
		return $this->parseToMML( $this->fname, $arguments, $state );
	}
}
