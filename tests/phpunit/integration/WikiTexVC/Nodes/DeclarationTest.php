<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use ArgumentCountError;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Declaration;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use TypeError;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\Declaration
 */
class DeclarationTest extends MediaWikiIntegrationTestCase {

	public function testEmptyDeclaration() {
		$this->expectException( ArgumentCountError::class );
		new Declaration();
		throw new ArgumentCountError( 'Should not create an empty Declaration' );
	}

	public function testOneArgumentDeclaration() {
		$this->expectException( ArgumentCountError::class );
		new Declaration( '\\f' );
		throw new ArgumentCountError( 'Should not create a Declaration with one argument' );
	}

	public function testIncorrectTypeDeclaration() {
		$this->expectException( TypeError::class );
		new Declaration( '\\f', 'x' );
		throw new RuntimeException( 'Should not create a Declaration with incorrect type' );
	}

	public function testBasicFunctionDeclaration() {
		$f = new Declaration( '\\rm', new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( '{\\rm {a}}', $f->render(), 'Should create a basic function' );
	}

	public function testGetters() {
		$f = new Declaration( '\\rm', new TexArray( new Literal( 'a' ) ) );
		$this->assertNotEmpty( $f->getFname() );
		$this->assertNotEmpty( $f->getArg() );
	}

	public function testTwoArgsFunctionDeclaration() {
		$f = new Declaration( '\\rm',
			new TexArray( new Literal( 'a' ), new Literal( 'b' ) ) );
		$this->assertEquals( '{\\rm {ab}}',
			$f->render(), 'Should create a function with two arguments' );
	}

	public function testCurliesDeclaration() {
		$f = new Declaration( '\\f', new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( '{\\f {a}}', $f->inCurlies(), 'Should create exactly one set of curlies' );
	}

	public function testExtractIdentifiersDeclaration() {
		$f = new Declaration( '\\rm', new TexArray( new Literal( 'a' ) ) );
		$this->assertEquals( [ 'a' ], $f->extractIdentifiers(), 'Should extract identifiers' );
	}

	public function testExtractNoIdentifiersDeclaration() {
		$f = new Declaration( '\\rm', new TexArray() );
		$this->assertEquals( [], $f->extractIdentifiers(), 'Should extract identifiers' );
	}

	public function testExtractIdentifiersMultiDeclaration() {
		$f = new Declaration( '\\rm', new TexArray( new Literal( 'a' ), new Literal( 'b' ) ) );
		$this->assertEquals( [ 'ab' ], $f->extractIdentifiers(), 'Should extract multiple identifiers' );
	}

	public function testNotExtractSomeSubscripts() {
		$f = new Declaration( '\\bf', new TexArray( new Literal( '' ) ) );
		$this->assertEquals( [], $f->extractSubscripts(),
			'Should not extract empty font modifier subscripts identifiers' );
	}

	public function testSubscriptsForFontMod() {
		$mods = [ 'rm', 'it', 'cal', 'bf' ];
		foreach ( $mods as $mod ) {
			$f = new Declaration( "\\{$mod}", new TexArray( new Literal( 'a' ) ) );
			$this->assertEquals( [ "\\math{$mod}{a}" ], $f->extractSubscripts(),
				"Should extract subscripts for {$mod} font modification" );
		}
	}

	public function testToMMLTree() {
		$f = new Declaration( '\\bf', new TexArray( new Literal( 'a' ) ) );
		$this->assertStringContainsString( '𝐚', $f->toMMLTree(), 'MathML should render bold' );
	}
}
