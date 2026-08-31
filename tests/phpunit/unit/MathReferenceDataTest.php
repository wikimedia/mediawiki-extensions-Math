<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2026 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\Tests;

use InvalidArgumentException;
use MediaWiki\Extension\Math\MathReferenceData;
use PHPUnit\Framework\TestCase;

/**
 * Tests the reusable regression reference data transformations.
 *
 * @covers \MediaWiki\Extension\Math\MathReferenceData
 */
class MathReferenceDataTest extends TestCase {

	public function testGroupCasesUsesInputHash(): void {
		$case = [ 'input' => 'x', 'output' => '<math>x</math>' ];

		$this->assertSame(
			[ md5( 'x' ) => $case ],
			MathReferenceData::groupCases( [ $case, $case ] )
		);
		$this->assertSame( 'md5', MathReferenceData::HASH_ALGORITHM );
	}

	public function testGroupCasesSortsByHash(): void {
		$references = MathReferenceData::groupCases( [
			[ 'input' => 'a', 'output' => 'a' ],
			[ 'input' => 'b', 'output' => 'b' ],
		] );
		$hashes = array_keys( $references );
		$sortedHashes = $hashes;
		sort( $sortedHashes );

		$this->assertSame( $sortedHashes, $hashes );
	}

	public function testGroupCasesGroupsParametersAndRemovesDuplicates(): void {
		$display = [ 'input' => 'x', 'output' => '<math display="block">x</math>' ];
		$inline = [
			'input' => 'x',
			'params' => [ 'display' => 'inline' ],
			'output' => '<math>x</math>',
		];

		$this->assertSame( [
			md5( 'x' ) => [
				'input' => 'x',
				'outputs' => [
					[ 'output' => '<math display="block">x</math>' ],
					[ 'params' => [ 'display' => 'inline' ], 'output' => '<math>x</math>' ],
				],
			],
		], MathReferenceData::groupCases( [ $display, $inline, $inline ] ) );
	}

	public function testGroupCasesRejectsMissingInput(): void {
		$this->expectException( InvalidArgumentException::class );
		MathReferenceData::groupCases( [ [ 'output' => 'x' ] ] );
	}

	public function testRenderReferenceEntryRejectsMissingInput(): void {
		$reference = [ 'output' => 'x' ];
		$this->expectException( InvalidArgumentException::class );
		MathReferenceData::renderReferenceEntry( $reference );
	}

	public function testRenderReferenceEntryRejectsEmptyOutputs(): void {
		$reference = [ 'input' => 'x', 'outputs' => [] ];
		$this->expectException( InvalidArgumentException::class );
		MathReferenceData::renderReferenceEntry( $reference );
	}

	public function testRenderReferenceEntryRejectsInvalidOutput(): void {
		$reference = [ 'input' => 'x', 'outputs' => [ 'invalid' ] ];
		$this->expectException( InvalidArgumentException::class );
		MathReferenceData::renderReferenceEntry( $reference );
	}

	public function testGroupCasesRejectsConflictingVariants(): void {
		$this->expectException( InvalidArgumentException::class );
		MathReferenceData::groupCases( [
			[ 'input' => 'x', 'params' => [ 'display' => 'inline' ], 'output' => 'first' ],
			[ 'input' => 'x', 'params' => [ 'display' => 'inline' ], 'output' => 'second' ],
		] );
	}
}
