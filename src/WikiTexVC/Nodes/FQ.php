<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsubsup;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunderover;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class FQ extends TexNode {

	/** @var TexNode */
	private $base;
	/** @var TexNode */
	private $up;
	/** @var TexNode */
	private $down;

	public function __construct( TexNode $base, TexNode $down, TexNode $up ) {
		parent::__construct( $base, $down, $up );
		$this->base = $base;
		$this->up = $up;
		$this->down = $down;
	}

	/**
	 * @return TexNode
	 */
	public function getBase(): TexNode {
		return $this->base;
	}

	/**
	 * @return TexNode
	 */
	public function getUp(): TexNode {
		return $this->up;
	}

	/**
	 * @return TexNode
	 */
	public function getDown(): TexNode {
		return $this->down;
	}

	/** @inheritDoc */
	public function render() {
		return $this->base->render() . '_' . $this->down->inCurlies() . '^' . $this->up->inCurlies();
	}

	/** @inheritDoc */
	public function toMMLTree( $arguments = [], &$state = [] ) {
		if ( array_key_exists( "limits", $state ) ) {
			// A specific FQ case with preceding limits, just invoke the limits parsing manually.
			return BaseParsing::limits( $this, $arguments, $state, "" );
		}
		$base = $this->getBase();
		if ( isset( $state['sideset'] ) &&
			$base->getLength() == 0 && !$base->isCurly() ) {
			// this happens when FQ is located in sideset Testcase 132
			return new MMLarray( new MMLmrow( TexClass::ORD, [], $this->getDown()->toMMLTree( [], $state ) ),
				new MMLmrow( TexClass::ORD, [], $this->getUp()->toMMLTree( [], $state ) ) );
		}
		$melement = new MMLmsubsup();
		// tbd check for more such cases like TexUtilTest 317
		if ( $base instanceof Literal ) {
			$litArg = trim( $base->getArgs()[0] );
			$tu = TexUtil::getInstance();
			// "sum", "bigcap", "bigcup", "prod" ... all are nullary macros.
			if ( $tu->nullary_macro( $litArg ) &&
				!$tu->is_literal( $litArg ) &&
				// by default (inline-displaystyle large operators should be used)
				( $state['styleargs']['displaystyle'] ?? 'true' ) === 'true'
			) {
				$melement = new MMLmunderover();
			}
		}

		$emptyMrow = "";
		// In cases with empty curly preceding like: "{}_1^2\!\Omega_3^4"
		if ( $base->isEmpty() ) {
			$emptyMrow = new MMLmrow();
		}
		// This seems to be the common case
		$inner = $melement::newSubtree(
			new MMLarray( $emptyMrow,
			$base->toMMLTree( [], $state ) ),
			new MMLmrow( TexClass::ORD, [], $this->getDown()->toMMLTree( $arguments, $state ) ),
			new MMLmrow( TexClass::ORD, [], $this->getUp()->toMMLTree( $arguments, $state ) ) );

		if ( $melement instanceof MMLmunderover ) {
			$args = $state['styleargs'] ?? [ "displaystyle" => "true", "scriptlevel" => 0 ];
			return new MMLmstyle( "", $args, $inner );
		}

		return $inner;
	}
}
