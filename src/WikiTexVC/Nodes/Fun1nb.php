<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;

class Fun1nb extends Fun1 {

	/** @inheritDoc */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/** @inheritDoc */
	public function render() {
		return $this->fname . ' ' . $this->arg->inCurlies() . ' ';
	}

	/** @inheritDoc */
	public function toMMLTree( array $arguments = [], array &$state = [] ): MMLbase {
		$cb = $this->getLocalCallback( trim( $this->fname ), $arguments, [], $state );
		if ( !$cb->isEmpty() ) {
			return $cb;
		}
		return $this->parseToMML( $this->fname, $arguments, $state );
	}

	protected function handleOperatorName( array $passedArgs, array $operatorContent,
		string $input, array $cb, array &$state
	): MMLbase {
		// In example "\\operatorname{a}"
		$applyFct = BaseParsing::getApplyFct( $state );
		$mmlNot = new MMLarray();
		if ( isset( $state['not'] ) && $state['not'] ) {
			$mmlNot = MMLParsingUtil::createNot();
		}
		$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::OP, 'mathvariant' => Variants::NORMAL ] );
		$argState = [ 'squashLiterals' => true ];
		$inner = $this->arg->toMMLtree( $passedArgs, $argState );
		if ( $inner instanceof MMLarray && count( $inner->getChildren() ) == 1 ) {
			$mi = $inner->getChildren()[0];
			// this check needs to be made explicit for phan
			if ( $mi instanceof MMLmi ) {
				$inner = $mi;
			}
		}
		return new MMLarray( $mmlNot, $inner, $applyFct );
	}
}
