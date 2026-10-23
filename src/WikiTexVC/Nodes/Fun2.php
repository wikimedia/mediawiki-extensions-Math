<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
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

	protected function intent( array $passedArgs, array $operatorContent,
		string $input, array $cb, array &$state
	): MMLbase {
		// if there is intent annotation add intent to root element
		// match args in row of subargs, unless an element has explicit annotations
		// nested annotations ?
		$arg1 = $this->arg1;
		$arg2 = $this->arg2;
		if ( !$arg2->isCurly() ) {
			return new MMLarray();
		}
		// tbd refactor intent form and fiddle in mml or tree
		$intentStr = MMLutil::squashLitsToUnitIntent( $arg2 );
		$intentContent = MMLParsingUtil::getIntentContent( $intentStr );
		$intentParams = MMLParsingUtil::getIntentParams( $intentContent );
		// Sometimes the intent has additioargs = {array[3]} nal args in the same string
		$intentArg = MMLParsingUtil::getIntentArgs( $intentStr );
		if ( !$intentContent && !$intentParams && $intentArg !== null ) {
			// explicit args annotation parsing in literal
			return $arg1->toMMLtree( [ "arg" => $intentArg ] );
		}
		$intentContentAtr = [ "intent" => $intentContent ];
		if ( $intentArg !== null ) {
			$intentContentAtr["arg"] = $intentArg;
		}
		// tbd refine intent params and operator content merging (does it overwrite ??)
		$intentParamsState = $intentParams ? [ "intent-params" => $intentParams ] : $state;
		// Here are some edge cases, they might go into toMMLTree of the related element
		if ( str_contains( $intentContent ?? '', "matrix" ) ||
			( $arg1->isCurly() && $arg1->getArgs()[0] instanceof Matrix ) ) {
			$element = $arg1->getArgs()[0];
			$rendered = $element->toMMLtree( [], $intentParamsState );
			return MMLParsingUtil::forgeIntentToSpecificElement( $rendered,
				$intentContentAtr, "mtable" );
		} elseif ( $arg1->isCurly() && count( $arg1->getArgs() ) >= 2 ) {
			// Create a surrounding element which holds the intents
			return new MMLmrow( "", $intentContentAtr, $arg1->toMMLtree( [], $intentParamsState ) );
		} elseif ( $arg1->isCurly() && count( $arg1->getArgs() ) >= 1 ) {
			// Forge the intent attribute to the top-level element after MML rendering
			$element = $arg1->getArgs()[0];
			$rendered = $element->toMMLtree( [], $intentParamsState );
			return MMLParsingUtil::forgeIntentToTopElement( $rendered, $intentContentAtr );
		} else {
			// This is the default case
			return $arg1->toMMLtree( $intentContentAtr, $intentParamsState );
		}
	}
}
