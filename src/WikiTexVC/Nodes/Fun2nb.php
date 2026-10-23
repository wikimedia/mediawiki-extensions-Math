<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmerror;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmmultiscripts;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunderover;

class Fun2nb extends Fun2 {

	/** @inheritDoc */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/** @inheritDoc */
	public function render() {
		return $this->fname . ' ' . $this->arg1->inCurlies() . $this->arg2->inCurlies();
	}

	protected function sideset( array $passedArgs, array $operatorContent,
		string $input, array $cb, array &$state
	): MMLbase {
		if ( !array_key_exists( "sideset", $state ) ) {
			return MMLmerror::newFromText( "Error parsing sideset expression, no succeeding operator found" );
		}

		$op = $state["sideset"];
		$argState = [ 'sideset' => true ];
		$in1 = $this->arg1->toMMLtree( [], $argState );
		$in2 = $this->arg2->toMMLtree( [], $argState );

		if ( $op instanceof FQ || $op instanceof DQ || $op instanceof UQ ) {
			$bm = new BaseMethods();
			if ( count( $op->getBase()->getArgs() ) == 1 ) {
				$baseOperator = $op->getBase()->getArgs()[0];
				if ( is_string( $baseOperator ) ) {
					$opParsed = $bm->checkAndParseOperator( $baseOperator,
						null, [ "largeop" => "true", "symmetric" => "true" ], [], null );
				} else {
					$opParsed = $baseOperator->toMMLTree();
				}
				if ( $opParsed->isEmpty() ) {
					$opParsed = $op->getBase()->toMMLtree();
				}
			} else {
				$opParsed = MMLmerror::newFromText( "Sideset operator parsing not implemented yet" );
			}
			$down = $op instanceof UQ ? new MMLmrow( "", [] ) : $op->getDown()->toMMLtree();
			$up = $op instanceof DQ ? new MMLmrow( "", [] ) : $op->getUp()->toMMLtree();
			return new MMLmrow(
				TexClass::OP,
				[],
				MMLmunderover::newSubtree(
					new MMLmstyle( "", [ "displaystyle" => "true" ],
					MMLmmultiscripts::newSubtree( $opParsed, $in2, new MMLarray(), $in1 ) ),
					new MMLmrow( "", [], $down ),
					new MMLmrow( "", [], $up )
				)
			);
		}

		if ( $op instanceof Literal ) {
			$bm = new BaseMethods();
			$opParsed = $bm->checkAndParseOperator( $op->getArg(), null, [], [], null );
			if ( $opParsed->isEmpty() ) {
				$opParsed = $op->toMMLtree();
			}
		} else {
			$opParsed = $op->toMMLtree();
		}
		return new MMLmrow( TexClass::OP, [],
			MMLmmultiscripts::newSubtree( $opParsed, $in2, new MMLarray(), $in1, new MMLarray(),
				"", [ Tag::ALIGN => "left" ]
			)
		);
	}
}
