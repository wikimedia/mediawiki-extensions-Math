<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;

class Fun4 extends TexNode {

	public function __construct(
		protected readonly string $fname,
		protected readonly TexNode $arg1,
		protected readonly TexNode $arg2,
		protected readonly TexNode $arg3,
		protected readonly TexNode $arg4,
	) {
		parent::__construct( $fname, $arg1, $arg2, $arg3, $arg4 );
	}

	public function getFname(): string {
		return $this->fname;
	}

	public function getArg1(): TexNode {
		return $this->arg1;
	}

	public function getArg2(): TexNode {
		return $this->arg2;
	}

	public function getArg3(): TexNode {
		return $this->arg3;
	}

	public function getArg4(): TexNode {
		return $this->arg4;
	}

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		return '{' . $this->fname . ' ' .
			$this->arg1->inCurlies() .
			$this->arg2->inCurlies() .
			$this->arg3->inCurlies() .
			$this->arg4->inCurlies() .
			'}';
	}

	/** @inheritDoc */
	public function toMMLTree( array $arguments = [], array &$state = [] ): MMLbase {
		$cb = $this->getLocalCallback( trim( $this->fname ), $arguments, [], $state );
		if ( !$cb->isEmpty() ) {
			return $cb;
		}
		return $this->parseToMML( $this->fname, $arguments, $state );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg1, $this->arg2, $this->arg3, $this->arg4 ];
		}
		return parent::extractIdentifiers( $args );
	}

	protected function mathChoice( array $passedArgs, array $operatorContent,
		string $input, array $cb, array &$state
	): MMLbase {
		$styleState = $state;

		/**
		 * Parametrization for mathchoice:
		 * \mathchoice
		 * {<material for display style>}
		 * {<material for text style>}
		 * {<material for script style>}
		 * {<material for scriptscript style>}
		 */

		if ( isset( $state["styleargs"] ) ) {
			$styleArgs = $state["styleargs"];
			$displayStyle = $styleArgs["displaystyle"] ?? "true";
			$scriptLevel = $styleArgs["scriptlevel"] ?? "0";

			if ( $displayStyle == "true" && $scriptLevel == "0" ) {
				// This is displaystyle
				return $this->arg1->toMMLtree( $passedArgs, $styleState );
			} elseif ( $displayStyle == "false" && $scriptLevel == "0" ) {
				// This is textstyle
				return $this->arg2->toMMLtree( $passedArgs, $styleState );
			} elseif ( $displayStyle == "false" && $scriptLevel == "1" ) {
				// This is scriptstyle
				return $this->arg3->toMMLtree( $passedArgs, $styleState );
			} elseif ( $displayStyle == "false" && $scriptLevel == "2" ) {
				// This is scriptscriptstyle
				return $this->arg4->toMMLtree( $passedArgs, $styleState );
			}
		}
		// By default render displaystyle
		return $this->arg1->toMMLtree( $passedArgs, $styleState );
	}
}
