<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsubsup;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunderover;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class FQ extends TexNode {

	public function __construct(
		private readonly TexNode $base,
		private readonly TexNode $down,
		private readonly TexNode $up,
	) {
		parent::__construct( $base, $down, $up );
	}

	public function getBase(): TexNode {
		return $this->base;
	}

	public function getUp(): TexNode {
		return $this->up;
	}

	public function getDown(): TexNode {
		return $this->down;
	}

	/** @inheritDoc */
	public function render() {
		return $this->base->render() . '_' . $this->down->inCurlies() . '^' . $this->up->inCurlies();
	}

	/** @inheritDoc */
	public function toMMLTree( $arguments = [], &$state = [] ) {
		$tu = TexUtil::getInstance();

		$hasLimits = array_key_exists( 'limits', $state );
		$displaystyle = ( $state['styleargs']['displaystyle'] ?? 'true' ) === 'true';

		if ( $hasLimits ) {
			// A specific FQ case with preceding limits, just invoke the limits parsing manually.
			$argsOp = [ 'form' => 'prefix' ];

			if ( !$displaystyle ) {
				$argsOp['movablelimits'] = 'true';
			}
			if ( $this->base->containsFunc( '\\nolimits' ) ) {
				$argsOp['movablelimits'] = 'false';
			}
			$base = $state['limits'];
		} else {
			$base = $this->getBase();
			$argsOp = $arguments;
		}

		if ( isset( $state['sideset'] ) &&
			$base->getLength() == 0 && !$base->isCurly() ) {
			// this happens when FQ is located in sideset Testcase 132
			return new MMLarray(
				new MMLmrow( TexClass::ORD, [], $this->getDown()->toMMLTree( [], $state ) ),
				new MMLmrow( TexClass::ORD, [], $this->getUp()->toMMLTree( [], $state ) )
			);
		}

		$above = false;

		if ( $base instanceof Literal ) {
			$litArg = trim( $base->getArgs()[0] );
			// use munderover if operator rendering indicates so
			$useMoveLimits = $tu->operator_rendering( $litArg )[1]['movesupsub'] ?? false;
			if ( $this->getBase()->containsFunc( '\\limits' ) || (
					$useMoveLimits &&
					( $argsOp['movablelimits'] ?? 'true' ) === 'true' &&
					$displaystyle
				) ) {
				if ( $this->getBase()->containsFunc( '\\limits' ) || ( $useMoveLimits && $displaystyle ) ) {
					$argsOp['movablelimits'] = 'false';
				}
				if ( !$useMoveLimits ) {
					unset( $argsOp['movablelimits'] );
				}
				$above = true;
			}
		} elseif ( $base instanceof Fun1 && $tu->over_operator( $base->getFname() ) ) {
			$above = true;
		} elseif ( $this instanceof DQ && $this->getBase()->containsFunc( "\underbrace" ) ) {
			$above = true;
		}

		if ( $this instanceof DQ && $hasLimits ) {
			$above = true;
		}
		if ( $this instanceof DQ && $this->isEmpty() ) {
			return null;
		}
		if ( $this instanceof DQ && $displaystyle && $tu->operator( trim( $base->render() ) ) ) {
			$above = true;
		}

		$emptyMrow = "";
		// In cases with empty curly preceding like: "{}_1^2\!\Omega_3^4"
		if ( $base->isEmpty() ) {
			$emptyMrow = new MMLmrow();
		}

		return $this->newMmlElement(
			$above,
			new MMLarray( $emptyMrow, $base->toMMLTree( $argsOp, $state ) ),
			new MMLmrow( TexClass::ORD, [], $this->getDown()->toMMLTree( $arguments, $state ) ),
			new MMLmrow( TexClass::ORD, [], $this->getUp()->toMMLTree( $arguments, $state ) )
		);
	}

	protected function newMmlElement( bool $above, MMLbase $base, MMLbase $down, MMLbase $up ): MMLbase {
		if ( $above ) {
			return MMLmunderover::newSubtree( $base, $down, $up );
		} else {
			return MMLmsubsup::newSubtree( $base, $down, $up );
		}
	}
}
