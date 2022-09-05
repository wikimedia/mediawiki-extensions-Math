<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\TexVC\Nodes;

class Infix extends TexNode {

	/** @var string */
	private $op;
	/** @var TexArray */
	private $arg1;
	/** @var TexArray */
	private $arg2;

	public function __construct( string $op, TexArray $arg1, TexArray $arg2 ) {
		parent::__construct( $op, $arg1, $arg2 );
		$this->op = $op;
		$this->arg1 = $arg1;
		$this->arg2 = $arg2;
	}

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return '{' . $this->arg1->render() .
			' ' . $this->op . ' ' .
			$this->arg2->render() . '}';
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg1, $this->arg2 ];
		}

		return parent::extractIdentifiers( $args );
	}

	public function name() {
		return 'INFIX';
	}
}
