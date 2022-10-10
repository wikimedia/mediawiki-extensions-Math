<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\TexVC\Nodes;

class ChemWord extends TexNode {

	/** @var TexNode */
	public $left;
	/** @var TexNode */
	public $right;

	public function __construct( TexNode $left, TexNode $right ) {
		parent::__construct( $left, $right );
		$this->left = $left;
		$this->right = $right;
	}

	/**
	 * @return TexNode
	 */
	public function getLeft(): TexNode {
		return $this->left;
	}

	/**
	 * @return TexNode
	 */
	public function getRight(): TexNode {
		return $this->right;
	}

	public function inCurlies() {
		return $this->render();
	}

	public function render() {
		return $this->left->render() . $this->right->render();
	}

	public function extractIdentifiers( $args = null ) {
		return [];
	}

	public function name() {
		return 'CHEM_WORD';
	}
}
