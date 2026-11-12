<?php

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use InvalidArgumentException;

class LengthSpec extends TexNode {
	/**
	 * MathJax's TeX unit table: swh:1:cnt:a5855f74cf49509f1215eb1121c8a56114e7e3ad;lines=80-81,104-114
	 * Unknown units count as pt: swh:1:cnt:a5855f74cf49509f1215eb1121c8a56114e7e3ad;lines=64-66
	 */
	private const UNIT_EM = [
		'em' => 1,
		'ex' => 0.43,
		'pt' => 0.1,
		'pc' => 1.2,
		'px' => 0.1,
		'in' => 7.2,
		'cm' => 7.2 / 2.54,
		'mm' => 7.2 / 25.4,
		'mu' => 1 / 18,
	];

	private readonly string $sign;
	private readonly string $number;

	public function __construct(
		?string $sign,
		array $number,
		private readonly string $unit,
	) {
		$this->sign = $sign ?? '';
		if ( count( $number ) === 3 ) {
			$this->number = implode( $number[0] ) . ( $number[1] ?? '' ) . implode( $number[2] );
		} elseif ( count( $number ) === 2 ) {
			$this->number = ( $number[0] ?? '' ) . implode( $number[1] );
		} else {
			throw new InvalidArgumentException( 'Invalid number in length spec' );
		}
		parent::__construct();
	}

	/** @inheritDoc */
	public function render() {
		return '[' . $this->sign . $this->number . $this->unit . ']';
	}

	public function getCssLength(): string {
		return $this->sign . $this->number . $this->unit;
	}

	public function toEm(): float {
		return (float)( $this->sign . $this->number ) * ( self::UNIT_EM[$this->unit] ?? self::UNIT_EM['pt'] );
	}

}
