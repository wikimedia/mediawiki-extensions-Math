<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Lr;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWikiIntegrationTestCase;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Lr
 */
class LrTest extends MediaWikiIntegrationTestCase {

	public function testEmptyLr() {
		$this->expectException( ArgumentCountError::class );
		new Lr();
		throw new ArgumentCountError( 'Should not create an empty lr' );
	}

	public function testOneArgumentLr() {
		$this->expectException( ArgumentCountError::class );
		new Lr( '(' );
		throw new ArgumentCountError( 'Should not create a lr with one argument' );
	}

	public function testIncorrectTypeLr() {
		$this->expectException( TypeError::class );
		new Lr( '(', ')', new Literal( 'a' ) );
		throw new TypeError( 'Should not create a lr with incorrect type' );
	}

	public function testBasicLr() {
		$f = new Lr( '(', ')', new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( '\\left(a\\right)', $f->render(), 'Should create a basic function' );
	}

	public function testGetters() {
		$f = new Lr( '(', ')', new TexArray( new Literal( 'a' ) ) );
		$this->assertNotEmpty( $f->getLeft() );
		$this->assertNotEmpty( $f->getRight() );
		$this->assertNotEmpty( $f->getArg() );
	}

	public function testCurliesLr() {
		$f = new Lr( '(', ')', new TexArray( new Literal( 'a' ), new Literal( 'b' ) ) );
		$this->assertEquals( '{\\left(ab\\right)}', $f->inCurlies(),
			'Should create exactly one set of curlies' );
	}

	public function testExtractIdentifiersLr() {
		$n = new Lr( '(', ')', new TexArray( new Literal( 'a' ), new Literal( 'b' ) ) );
		$this->assertEquals( [ 'a', 'b' ], $n->extractIdentifiers(),
			'Should extract identifiers' );
	}

	public function testRenderA() {
		$n = new Lr( '(', ')', new TexArray( new Literal( 'A' ) ) );
		$mml = $n->toMMLTree();
		$this->assertStringContainsString( 'A</mi>', $mml );
		$this->assertStringContainsString( ')</mo>', $mml );
	}

	public function testRenderADeriv() {
		$n = new Lr( '(', ')', new TexArray( new Literal( 'A' ) ) );
		$state = [ 'deriv' => 1 ];
		$mml = $n->toMMLTree( [], $state );
		$this->assertStringNotContainsString( '&#x2032;</mo>', $mml );
	}

}
