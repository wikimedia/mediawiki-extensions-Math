<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

class MMLarray extends MMLbase {

	/**
	 * Constructor for array wrapper of MMLbase elements
	 *
	 * @param MMLbase ...$children MMLbase child elements ...$children
	 */
	public function __construct( MMLbase ...$children ) {
		parent::__construct( '', '', [], ...$children );
	}

	public function __toString(): string {
		return implode( '', $this->children );
	}

	/**
	 * An array behaves differently as it's not a real MathML element.
	 * Is empty if all of its children are empty.
	 */
	public function isEmpty(): bool {
		foreach ( $this->children as $child ) {
			if ( !$child->isEmpty() ) {
				return false;
			}
		}
		return true;
	}

	public function getTextContent(): string {
		$tmp = '';
		foreach ( $this->getChildren() as $child ) {
			$tmp .= $child->getTextContent();
		}
		return $tmp;
	}
}
