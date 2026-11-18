<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Fun1nb extends Fun1 {

	/** @inheritDoc */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/** @inheritDoc */
	public function render() {
		return $this->fname . ' ' . $this->arg->inCurlies() . ' ';
	}

	/** MathJax HandleOperatorName: swh:1:cnt:d1ea310dac6fcde3604a6155f33b8935b1e17349;lines=348-396 */
	protected function handleOperatorName( RenderContext $ctx ): MMLbase {
		$mmlNot = $ctx->negated ? MMLParsingUtil::createNot() : new MMLarray();
		$inner = $this->renderArg( $ctx );
		// The letters of the name, merged into one literal, are an operator
		if ( $this->arg instanceof Literal || ( $this->arg instanceof TexArray && $this->arg->toOneLiteral() ) ) {
			MMLParsingUtil::addAttributesToFirstElement( $inner, [ Tag::CLASSTAG => TexClass::OP ] );
		}
		if ( $inner instanceof MMLarray && count( $inner->getChildren() ) == 1 ) {
			$mi = $inner->getChildren()[0];
			// this check needs to be made explicit for phan
			if ( $mi instanceof MMLmi ) {
				$inner = $mi;
			}
		}
		return new MMLarray( $mmlNot, $inner, BaseParsing::getApplyFct( $ctx ) );
	}
}
