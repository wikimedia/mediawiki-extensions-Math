<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\TexVC\Nodes;

class UQ extends TexNode {

	/** @var TexNode */
	private $base;
	/** @var TexNode */
	private $up;

	public function __construct( TexNode $base, TexNode $up ) {
		parent::__construct( $base, $up );
		$this->base = $base;
		$this->up = $up;
	}

	/**
	 * @return TexNode
	 */
	public function getBase(): TexNode {
		return $this->base;
	}

	/**
	 * @return TexNode
	 */
	public function getUp(): TexNode {
		return $this->up;
	}

	public function render() {
		return $this->base->render() . '^' . $this->up->inCurlies();
	}

	public function name() {
		return 'UQ';
	}
}
