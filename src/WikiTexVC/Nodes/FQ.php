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
	public function toMMLTree( $arguments = [], &$state = [] ): MMLbase {
		// The operator before \limits or \nolimits, see TexArray::checkForLimits
		$base = $state['limits'] ?? $this->getBase();
		unset( $state['limits'] );

		// Special-case: sideset with empty (non-curly) base -> return array of under/over rows.
		if ( isset( $state['sideset'] ) && $base->getLength() === 0 && !$base->isCurly() ) {
			return new MMLarray(
				new MMLmrow( TexClass::ORD, [], $this->getDown()->toMMLTree( [], $state ) ),
				new MMLmrow( TexClass::ORD, [], $this->getUp()->toMMLTree( [], $state ) )
			);
		}

		// TeX's make_op: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=14684-14685
		$limits = $this->getLimits( $base );
		$displaystyle = ( $state['styleargs']['displaystyle'] ?? 'true' ) === 'true';
		$above = $limits === 'limits' || ( $limits === 'displaylimits' && $displaystyle );

		$baseMML = $base->toMMLTree( $arguments, $state );
		if ( $this instanceof DQ && $this->isEmpty() ) {
			return new MMLarray();
		}

		$emptyMrow = $base->isEmpty() ? new MMLmrow() : new MMLarray();

		// TeX sets scripts in script style, which is no display style.
		$scriptState = $state;
		$scriptState['styleargs']['displaystyle'] = 'false';
		$down = new MMLmrow( TexClass::ORD, [], $this->getDown()->toMMLTree( $arguments, $scriptState ) );
		$up = new MMLmrow( TexClass::ORD, [], $this->getUp()->toMMLTree( $arguments, $scriptState ) );

		return $this->newMmlElement( $above, new MMLarray( $emptyMrow, $baseMML ), $down, $up );
	}

	/**
	 * Limits setting of the Op atom $base: limits, nolimits, displaylimits, or null for other atoms.
	 */
	private function getLimits( TexNode $base ): ?string {
		$tu = TexUtil::getInstance();
		// \limits and \nolimits are the base of the scripts and override the operator before them.
		// TeX's math_limit_switch: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=22024-22033
		return $tu->op_limits( $this->getBase()->getFname() ?? '' ) ?:
			$tu->op_limits( $base->getFname() ?? '' ) ?: null;
	}

	protected function newMmlElement( bool $above, MMLbase $base, MMLbase $down, MMLbase $up ): MMLbase {
		return $above
			? MMLmunderover::newSubtree( $base, $down, $up )
			: MMLmsubsup::newSubtree( $base, $down, $up );
	}
}
