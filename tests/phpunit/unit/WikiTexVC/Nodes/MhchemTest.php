<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Mhchem;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Mhchem
 */
class MhchemTest extends MediaWikiUnitTestCase {

	public function testEmptyMhchem() {
		$this->expectException( ArgumentCountError::class );
		new Mhchem();
		throw new ArgumentCountError( 'Should not create an empty Mhchem' );
	}

	public function testOneArgumentMhchem() {
		$this->expectException( ArgumentCountError::class );
		new Mhchem( '\\f' );
		throw new ArgumentCountError( 'Should not create a Mhchem with one argument' );
	}

	public function testIncorrectTypeMhchem() {
		$this->expectException( TypeError::class );
		new Mhchem( '\\f', 'x' );
		throw new TypeError( 'Should not create a Mhchem with incorrect type' );
	}

	private static function parseFirst( string $tex ): Mhchem {
		$node = ( new TexVC() )->parse( $tex, [ 'usemhchem' => true ] )->first();
		self::assertInstanceOf( Mhchem::class, $node );
		return $node;
	}

	private static function elementNames( MMLbase $mml ): array {
		$names = [ $mml->getName() ];
		foreach ( $mml->getChildren() as $child ) {
			if ( $child instanceof MMLbase ) {
				array_push( $names, ...self::elementNames( $child ) );
			}
		}
		return $names;
	}

	public static function provideConverted() {
		return [
			'ce' => [ '\\ce{H2O}', '\\ce', '{\\mathrm {H} {\\vphantom {A}}_{\\smash[{t}]{2}}\\mathrm {O} }' ],
			'pu' => [ '\\pu{123 kJ}', '\\pu', '{123~\\mathrm {kJ} }' ],
			'unbraced' => [ '\\ce A', '\\ce', '{\\mathrm {A} }' ],
		];
	}

	/**
	 * @dataProvider provideConverted
	 */
	public function testRenderGivesConvertedTex( string $tex, string $fname, string $converted ) {
		$node = self::parseFirst( $tex );
		$this->assertSame( $fname, $node->getFname() );
		$this->assertSame( $converted, $node->render() );
		$this->assertSame( '{' . $converted . '}', $node->inCurlies() );
	}

	public function testSuperscriptKeepsBraces() {
		$result = ( new TexVC() )->check( 'x^\\ce A', [ 'usemhchem' => true ] );
		$this->assertSame( 'x^{{\\mathrm {A} }}', $result['output'] );
	}

	public function testMathMLIsThatOfTheConvertedTex() {
		$node = self::parseFirst( '\\ce{H2O}' );
		$this->assertEquals( $node->getArg()->toMMLTree(), $node->toMMLTree() );
	}

	public function testNestedCeIsConvertedInside() {
		$node = self::parseFirst( '\\ce{\\overbrace{\\ce{H2O}}}' );
		$this->assertStringStartsWith( '{\\overbrace {{\\mathrm {H} ', $node->render() );
		$this->assertStringNotContainsString( '\\ce', $node->render() );
		$this->assertContains( 'mover', self::elementNames( $node->toMMLTree() ) );
	}

	public function testExtractIdentifiersMhchem() {
		$n = new Mhchem( '\\f', new Literal( 'a' ) );
		$this->assertEquals( [], $n->extractIdentifiers(),
			'Should extract identifiers' );
	}
}
