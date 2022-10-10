<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\TexVC\Nodes;

class Lr extends TexNode {

	/** @var string */
	private $left;
	/** @var string */
	private $right;
	/** @var TexArray */
	private $arg;

	public function __construct( string $left, string $right, TexArray $arg ) {
		parent::__construct( $left, $right, $arg );
		$this->left = $left;
		$this->right = $right;
		$this->arg = $arg;
	}

	/**
	 * @return string
	 */
	public function getLeft(): string {
		return $this->left;
	}

	/**
	 * @return string
	 */
	public function getRight(): string {
		return $this->right;
	}

	/**
	 * @return TexArray
	 */
	public function getArg(): TexArray {
		return $this->arg;
	}

	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	public function render() {
		return '\\left' . $this->left . $this->arg->render() . '\\right' . $this->right;
	}

	public function containsFunc( $target, $args = null ) {
		if ( $args == null ) {
			$args = [ '\\left','\\right', $this->arg ];
		}
		return parent::containsFunc( $target, $args );
	}

	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg ];
		}
		return parent::extractIdentifiers( $args );
	}

	public function name() {
		return 'LR';
	}
}
