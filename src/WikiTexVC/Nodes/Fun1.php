<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class Fun1 extends TexNode {

	public function __construct(
		protected readonly string $fname,
		protected readonly TexNode $arg,
	) {
		parent::__construct( $fname, $arg );
	}

	public function getFname(): string {
		return $this->fname;
	}

	public function getArg(): TexNode {
		return $this->arg;
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
	public function toMMLTree( array $arguments = [], array &$state = [] ): MMLbase {
		$cb = $this->getLocalCallback( trim( $this->fname ), $arguments, [], $state );
		if ( !$cb->isEmpty() ) {
			return $cb;
		}
		return $this->parseToMML( $this->fname, $arguments, null );
	}

	public function createMover( string $inner, array $moArgs = [] ): MMLbase {
		return new MMLmrow( TexClass::ORD, [],
			new MMLmrow( TexClass::ORD, [],
				( new MMLmover() )::newSubtree( $this->args[1]->toMMLTree(),
					new MMLmo( "", $moArgs, $inner ) )
			)
		);
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

	protected function lap(): MMLmrow {
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
			new MMLmpadded( "", $args, $this->getArg()->toMMLTree() ) );
	}

	/** MathJax \pmod and \pod: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=699-709 */
	protected function pmod( array $passedArgs, array $operatorContent,
		string $input, array $cb, array &$state
	): MMLbase {
		// amsmath \pod and \pmod: \mkern8mu (inline) and \mkern6mu
		// swh:1:cnt:e05c33e5d589cd1cb1ab6d74840bb02ea6f08273;lines=2068-2070
		return new MMLmrow( TexClass::ORD, [],
			new MMLmspace( "", [ "width" => MMLutil::round2em( 8 / 18 ) ] ),
			new MMLmo( "", [ "stretchy" => "false" ], "(" ),
			new MMLmi( "", [], "mod" ),
			new MMLmspace( "", [ "width" => MMLutil::round2em( 6 / 18 ) ] ),
			$this->arg->toMMLTree(),
			new MMLmo( "", [ "stretchy" => "false" ], ")" )
		);
	}

	/** MathJax \bmod: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=695-698 */
	protected function bmod( array $passedArgs, array $operatorContent,
		string $input, array $cb, array &$state
	): MMLbase {
		// amsmath \bmod: \mkern5mu on both sides
		// swh:1:cnt:e05c33e5d589cd1cb1ab6d74840bb02ea6f08273;lines=2065-2067
		$thick = MMLutil::round2em( 5 / 18 );
		// FIXME: the trailing thin space has no known source
		$thin = new MMLmspace( "", [ "width" => MMLutil::round2em( 3 / 18 ) ] );
		return new MMLmrow( TexClass::ORD, [],
			new MMLmo( "", [ "lspace" => $thick, "rspace" => $thick ], "mod" ),
			new MMLmrow( TexClass::ORD, [], $this->arg->toMMLTree() ),
			new MMLmrow( TexClass::ORD, [], $thin ) );
	}
}
