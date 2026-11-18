<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsubsup;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunderover;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
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

	public function renderBase( RenderContext $ctx ): MMLbase {
		// Not renderChild(): the grammar makes the apostrophe of D'_\mu a base, which stays a literal
		return $this->base->toMMLTree( $this->argContext( $this->base, $ctx ) );
	}

	public function renderDown( RenderContext $ctx ): MMLbase {
		return $this->renderChild( $this->down, $ctx );
	}

	public function renderUp( RenderContext $ctx ): MMLbase {
		return $this->renderChild( $this->up, $ctx );
	}

	/** @inheritDoc */
	public function render() {
		return $this->base->render() . '_' . $this->down->inCurlies() . '^' . $this->up->inCurlies();
	}

	/** @inheritDoc */
	public function argContext( TexNode $arg, RenderContext $ctx, array $links = [], ?array $colors = null
	): RenderContext {
		// tex.web make_scripts: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=14916-14928
		$style = match ( $arg ) {
			$this->down => RenderContext::nextStyle( $ctx->style, 'sub' ),
			$this->up => RenderContext::nextStyle( $ctx->style, 'sup' ),
			default => $ctx->style,
		};
		// \operatorname squashes the literals of its scripts
		return $ctx->forArgument( $style, $ctx->font, squashLiterals: $ctx->squashLiterals,
			colorDefinitions: $colors, links: $links );
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		// The operator before \limits or \nolimits, see TexArray::checkForLimits
		$base = $ctx->limitsBase ?? $this->base;

		// Special-case: sideset with empty (non-curly) base -> return array of under/over rows.
		if ( $ctx->inSideset && $base->getLength() === 0 && !$base->isCurly() ) {
			return new MMLarray(
				new MMLmrow( TexClass::ORD, [], $this->renderDown( $ctx ) ),
				new MMLmrow( TexClass::ORD, [], $this->renderUp( $ctx ) )
			);
		}

		// TeX's make_op: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=14684-14685
		$limits = $this->getLimits( $base );
		$above = $limits === 'limits' || ( $limits === 'displaylimits' && $ctx->isDisplayStyle() );

		// The operator before \limits is a neighbour of the scripts in their group
		$baseMML = $ctx->limitsBase
			? $base->toMMLTree( $ctx->forGroupMember() )
			: $this->renderBase( $ctx );
		if ( $this instanceof DQ && $this->isEmpty() ) {
			return new MMLarray();
		}

		$emptyMrow = $base->isEmpty() ? new MMLmrow() : new MMLarray();

		$down = new MMLmrow( TexClass::ORD, [], $this->renderDown( $ctx ) );
		$up = new MMLmrow( TexClass::ORD, [], $this->renderUp( $ctx ) );

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
