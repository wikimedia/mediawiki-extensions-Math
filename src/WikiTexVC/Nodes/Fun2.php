<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;

class Fun2 extends TexNode {

	public function __construct(
		protected readonly string $fname,
		protected readonly TexNode $arg1,
		protected readonly TexNode $arg2,
	) {
		parent::__construct( $fname, $arg1, $arg2 );
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

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		return '{' . $this->fname . ' ' . $this->arg1->inCurlies() . $this->arg2->inCurlies() . '}';
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
			$args = [ $this->arg1, $this->arg2 ];
		}
		return parent::extractIdentifiers( $args );
	}

	/** MathJax \stackrel: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=522 */
	protected function stackrel( array $passedArgs, array $operatorContent,
		string $input, array $cb, array &$state
	): MMLbase {
		$inner = MMLmover::newSubtree(
			new MMLmrow( TexClass::OP, [], $this->arg2->toMMLTree() ),
			new MMLmrow( TexClass::ORD, [], $this->arg1->toMMLTree() )
		);
		return new MMLmrow( TexClass::ORD, [], new MMLmrow( TexClass::REL, [], $inner ) );
	}

}
