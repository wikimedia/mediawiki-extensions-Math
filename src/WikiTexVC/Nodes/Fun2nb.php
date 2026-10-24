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
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Fun2nb extends Fun2 {

	/** @inheritDoc */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/** @inheritDoc */
	public function render() {
		return $this->fname . ' ' . $this->arg1->inCurlies() . $this->arg2->inCurlies();
	}

	/** MathJax SideSet: swh:1:cnt:d1ea310dac6fcde3604a6155f33b8935b1e17349;lines=404-495 */
	protected function sideset( RenderContext $ctx ): MMLbase {
		$op = $ctx->sidesetOperator;
		if ( !$op ) {
			return MMLmerror::newFromText( "Error parsing sideset expression, no succeeding operator found" );
		}

		$in1 = $this->renderArg1( $ctx );
		$in2 = $this->renderArg2( $ctx );

		// The operator is a neighbour of \sideset in its group
		$opCtx = $ctx->forGroupMember();
		if ( $op instanceof FQ ) {
			$baseCtx = $op->argContext( $op->getBase(), $opCtx );
			$bm = new BaseMethods();
			if ( count( $op->getBase()->getArgs() ) == 1 ) {
				$baseOperator = $op->getBase()->getArgs()[0];
				if ( is_string( $baseOperator ) ) {
					$opParsed = $bm->checkAndParseOperator( $baseOperator,
						null, [ "largeop" => "true", "symmetric" => "true" ], [] );
				} else {
					$opParsed = $op->getBase()->renderChild( $baseOperator, $baseCtx );
				}
				if ( $opParsed->isEmpty() ) {
					$opParsed = $op->getBase()->toMMLTree( $baseCtx );
				}
			} else {
				$opParsed = MMLmerror::newFromText( "Sideset operator parsing not implemented yet" );
			}
			$down = $op instanceof UQ ? new MMLmrow( "", [] ) : $op->renderDown( $opCtx );
			$up = $op instanceof DQ ? new MMLmrow( "", [] ) : $op->renderUp( $opCtx );
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
			$opParsed = $bm->checkAndParseOperator( $op->getArg(), null, [], [] );
			if ( $opParsed->isEmpty() ) {
				$opParsed = $op->toMMLTree( $opCtx );
			}
		} else {
			$opParsed = $op->toMMLTree( $opCtx );
		}
		return new MMLmrow( TexClass::OP, [],
			MMLmmultiscripts::newSubtree( $opParsed, $in2, new MMLarray(), $in1, new MMLarray(),
				"", [ Tag::ALIGN => "left" ]
			)
		);
	}
}
