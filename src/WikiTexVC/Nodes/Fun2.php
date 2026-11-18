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
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;

class Fun2 extends TexNode {

	public function __construct(
		protected readonly string $fname,
		protected readonly TexNode $arg1,
		protected readonly TexNode $arg2,
	) {
		parent::__construct( $fname, $arg1, $arg2 );
	}

	/** @inheritDoc */
	public function argContext( TexNode $arg, RenderContext $ctx, array $links = [], ?array $colors = null
	): RenderContext {
		$style = $this->argStyle( $arg, $ctx->style );
		switch ( self::macro( $this->fname )['callback'] ) {
			case 'sideset':
				return $ctx->forArgument( $style, $ctx->font, inSideset: true, colorDefinitions: $colors,
					links: $links );
			case 'intent':
				// The parameters of the intent annotation apply to the annotated argument
				if ( $arg === $this->arg1 ) {
					return $ctx->forArgument( $style, $ctx->font, intentParams: $this->getIntent()[1] ?: null,
						colorDefinitions: $colors, links: $links );
				}
		}
		return parent::argContext( $arg, $ctx, $links, $colors );
	}

	/** @inheritDoc */
	protected function argStyle( TexNode $arg, int $style ): int {
		return self::macroArgStyle( $this->fname, $arg === $this->arg1 ? 0 : 1, $style );
	}

	/**
	 * The annotation of \intent: its intent, the parameters in it and the arg name; nulls if it is no group
	 * @return array{?string,?string[],?string}
	 */
	public function getIntent(): array {
		if ( !$this->arg2->isCurly() ) {
			return [ null, null, null ];
		}
		$annotation = MMLutil::squashLitsToUnitIntent( $this->arg2 );
		$intent = MMLParsingUtil::getIntentContent( $annotation );
		return [ $intent, MMLParsingUtil::getIntentParams( $intent ), MMLParsingUtil::getIntentArgs( $annotation ) ];
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

	public function renderArg1( RenderContext $ctx ): MMLbase {
		return $this->renderChild( $this->arg1, $ctx );
	}

	public function renderArg2( RenderContext $ctx ): MMLbase {
		return $this->renderChild( $this->arg2, $ctx );
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
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		return $this->parseToMML( $this->fname, $ctx );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg1, $this->arg2 ];
		}
		return parent::extractIdentifiers( $args );
	}

	protected function intent( RenderContext $ctx ): MMLbase {
		// if there is intent annotation add intent to root element
		// match args in row of subargs, unless an element has explicit annotations
		// nested annotations ?
		$arg1 = $this->arg1;
		if ( !$this->arg2->isCurly() ) {
			return new MMLarray();
		}
		// Sometimes the intent has additional args in the same string
		[ $intentContent, $intentParams, $intentArg ] = $this->getIntent();
		if ( !$intentContent && !$intentParams && $intentArg !== null ) {
			// explicit args annotation parsing in literal
			return MMLParsingUtil::addAttributesToFirstElement( $this->renderArg1( $ctx ), [ "arg" => $intentArg ] );
		}
		$intentContentAtr = [ "intent" => $intentContent ];
		if ( $intentArg !== null ) {
			$intentContentAtr["arg"] = $intentArg;
		}
		// Here are some edge cases, they might go into toMMLTree of the related element
		$arg1Ctx = $this->argContext( $arg1, $ctx );
		if ( str_contains( $intentContent ?? '', "matrix" ) ||
			( $arg1->isCurly() && $arg1->getArgs()[0] instanceof Matrix ) ) {
			$element = $arg1->getArgs()[0];
			$rendered = $arg1->renderChild( $element, $arg1Ctx );
			return MMLParsingUtil::forgeIntentToSpecificElement( $rendered,
				$intentContentAtr, "mtable" );
		} elseif ( $arg1->isCurly() && count( $arg1->getArgs() ) >= 2 ) {
			// Create a surrounding element which holds the intents
			return new MMLmrow( "", $intentContentAtr, $this->renderArg1( $ctx ) );
		} elseif ( $arg1->isCurly() && count( $arg1->getArgs() ) >= 1 ) {
			// Forge the intent attribute to the top-level element after MML rendering
			$element = $arg1->getArgs()[0];
			$rendered = $arg1->renderChild( $element, $arg1Ctx );
			return MMLParsingUtil::forgeIntentToTopElement( $rendered, $intentContentAtr );
		} else {
			// This is the default case
			return MMLParsingUtil::addAttributesToFirstElement( $this->renderArg1( $ctx ), $intentContentAtr );
		}
	}

	/** MathJax \stackrel: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=522 */
	protected function stackrel( RenderContext $ctx ): MMLbase {
		$inner = MMLmover::newSubtree(
			new MMLmrow( TexClass::OP, [], $this->renderArg2( $ctx ) ),
			new MMLmrow( TexClass::ORD, [], $this->renderArg1( $ctx ) )
		);
		return new MMLmrow( TexClass::ORD, [], new MMLmrow( TexClass::REL, [], $inner ) );
	}

}
