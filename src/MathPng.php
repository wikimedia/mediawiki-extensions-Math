<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2018 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

class MathPng extends MathMathML {
	public function __construct( $tex = '', array $params = [] ) {
		parent::__construct( $tex, $params );
		$this->setMode( 'png' );
	}

	public function getHtmlOutput() {
		return $this->getFallbackImage();
	}

}
