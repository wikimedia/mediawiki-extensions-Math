<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class Fun1 extends TexNode {

	public function __construct(
		protected readonly string $fname,
		protected readonly TexNode $arg,
	) {
		parent::__construct( $fname, $arg );
	}

	/** @inheritDoc */
	public function argContext( TexNode $arg, RenderContext $ctx, array $links = [], ?array $colors = null
	): RenderContext {
		$macro = self::macro( $this->fname );
		// \operatorname squashes the letters of its name, the text macros those of their group, see hBox()
		$squash = $macro['callback'] === 'handleOperatorName' || ( $macro['textFont'] !== null && $arg->isCurly() );
		if ( !$squash ) {
			return parent::argContext( $arg, $ctx, $links, $colors );
		}
		$font = $this->macroFont( $ctx->font ) ?? $ctx->font;
		return $ctx->forArgument( $this->argStyle( $arg, $ctx->style ), $font,
			inText: $macro['textFont'] !== null ? true : null, squashLiterals: true, colorDefinitions: $colors,
			links: $links );
	}

	public function getFname(): string {
		return $this->fname;
	}

	public function getArg(): TexNode {
		return $this->arg;
	}

	public function renderArg( RenderContext $ctx ): MMLbase {
		return $this->renderChild( $this->arg, $ctx );
	}

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		return '{' . $this->fname . ' ' . $this->arg->inCurlies() . '}';
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		return $this->parseToMML( $this->fname, $ctx );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = [ $this->arg ];
		}
		$tu = TexUtil::getInstance();
		$letterMods = array_keys( $tu->getBaseElements()['is_letter_mod'] );
		if ( in_array( $this->fname, $letterMods, true ) ) {
			$ident = $this->arg->getModIdent();
			if ( !isset( $ident[0] ) ) {
				return parent::extractIdentifiers( $args );
			}
			// in difference to javascript code: taking first element of array here.
			return [ $this->fname . '{' . $ident[0] . '}' ];

		} elseif ( array_key_exists( $this->fname, $tu->getBaseElements()['ignore_identifier'] ) ) {
			return [];
		}

		return parent::extractIdentifiers( $args );
	}

	/** @inheritDoc */
	public function extractSubscripts() {
		return $this->getSubs( $this->arg->extractSubscripts() );
	}

	/** @inheritDoc */
	public function getModIdent() {
		return $this->getSubs( $this->arg->getModIdent() );
	}

	private function getSubs( array $subs ): array {
		$letterMods = array_keys( TexUtil::getInstance()->getBaseElements()['is_letter_mod'] );

		if ( isset( $subs[0] ) && in_array( $this->fname, $letterMods, true ) ) {
			// in difference to javascript code: taking first element of array here.
			return [ $this->fname . '{' . $subs[0] . '}' ];
		}
		return [];
	}

	/** MathJax Accent: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=801-829 */
	protected function accent( RenderContext $ctx, string $accent,
		int|bool|null $stretchy = null
	): MMLbase {
		// TODO The first if-clause is mathjax specific (and not necessary by generic parsers)
		// and will most probably removed (just for running all tc atm)
		if ( $accent == "00B4" || $accent == "0060" ) {
			$attrs = [ Tag::SCRIPTTAG => "true" ];
		} else {
			if ( $stretchy === null ) {
				$attrs = [];
			} else {
				$attrs = [ "stretchy" => $stretchy ? "true" : "false" ];
			}
		}
		if ( trim( $this->fname ) === '\\vec' ) {
			$attrs['class'] = 'mwe-math-vec';
		}

		// Fetching entity from $accent key tbd
		$entity = MMLutil::createEntity( $accent );
		if ( !$entity ) {
			$entity = $accent;
		}
		$inner = $this->renderArg( $ctx );

		return MMLmover::newSubtree(
			!$inner->isEmpty() ? $inner : ( new MMLmrow() ),
			new MMLmo( '', $attrs, $entity )
		);
	}

	/**
	 * The text macros like \textbf, which name their font in texutil.json
	 * MathJax HBox: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1415-1420
	 */
	protected function hBox( RenderContext $ctx ): MMLbase {
		$text = $this->renderArg( $ctx )->getTextContent();
		return new MMLmtext( "", $this->textFontArgs(), $text );
	}

	/** MathJax Lap: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1213-1224 */
	protected function lap( RenderContext $ctx ): MMLmrow {
		$name = $this->fname;
		if ( trim( $name ) === "\\rlap" ) {
			$args = [ "width" => "0" ];
		} elseif ( trim( $name ) === "\\llap" ) {
			$args = [ "width" => "0", "lspace" => "-1width" ];
		} else {
			throw new InvalidArgumentException(
				"Unsupported function for lap: $name"
			);
		}
		return new MMLmrow( TexClass::ORD, [],
			new MMLmpadded( "", $args, $this->renderArg( $ctx ) ) );
	}

	/** MathJax \pmod and \pod: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=699-709 */
	protected function pmod( RenderContext $ctx ): MMLbase {
		// amsmath \pod and \pmod: \mkern8mu (inline) and \mkern6mu
		// swh:1:cnt:e05c33e5d589cd1cb1ab6d74840bb02ea6f08273;lines=2068-2070
		return new MMLmrow( TexClass::ORD, [],
			new MMLmspace( "", [ "width" => MMLutil::round2em( 8 / 18 ) ] ),
			new MMLmo( "", [ "stretchy" => "false" ], "(" ),
			new MMLmi( "", [], "mod" ),
			new MMLmspace( "", [ "width" => MMLutil::round2em( 6 / 18 ) ] ),
			$this->renderArg( $ctx ),
			new MMLmo( "", [ "stretchy" => "false" ], ")" )
		);
	}

	/** MathJax \bmod: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=695-698 */
	protected function bmod( RenderContext $ctx ): MMLbase {
		// amsmath \bmod: \mkern5mu on both sides
		// swh:1:cnt:e05c33e5d589cd1cb1ab6d74840bb02ea6f08273;lines=2065-2067
		$thick = MMLutil::round2em( 5 / 18 );
		// FIXME: the trailing thin space has no known source
		$thin = new MMLmspace( "", [ "width" => MMLutil::round2em( 3 / 18 ) ] );
		return new MMLmrow( TexClass::ORD, [],
			new MMLmo( "", [ "lspace" => $thick, "rspace" => $thick ], "mod" ),
			new MMLmrow( TexClass::ORD, [], $this->renderArg( $ctx ) ),
			new MMLmrow( TexClass::ORD, [], $thin ) );
	}
}
