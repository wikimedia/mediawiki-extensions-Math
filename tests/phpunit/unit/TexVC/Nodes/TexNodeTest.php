<?php

namespace MediaWiki\Extension\Math\Tests\TexVC\Nodes;

use InvalidArgumentException;
use MediaWiki\Extension\Math\TexVC\Nodes\TexNode;
use MediaWikiUnitTestCase;
use RuntimeException;

/**
 * @covers \MediaWiki\Extension\Math\TexVC\Nodes\TexNode
 */
class TexNodeTest extends MediaWikiUnitTestCase {

	public function provideTexToRender() {
		return [
			[ [], '' ],
			[ [ '' ], '' ],
			[ [ 'hello', ' ', 'world' ], 'hello world' ],
			[ [ 'hello', new TexNode( ' ' ), new TexNode( new TexNode( 'world' ) ) ], 'hello world' ],
		];
	}

	/**
	 * @dataProvider provideTexToRender
	 */
	public function testRender( array $args, string $expected ) {
		$n = new TexNode( ...$args );
		$this->assertSame( $expected, $n->render() );
	}

	public function testIntegerArgs() {
		$this->expectException( InvalidArgumentException::class );
		( new TexNode( 1 ) )->render();
		throw new RuntimeException( 'Should not accept integers as arguments' );
	}

	public function provideTexWithoutCurlies() {
		return [
			[ 'a', '{a}' ],
			[ new TexNode( 'a' ), '{a}' ],
			[ '', '{}' ],
		];
	}

	/**
	 * @dataProvider provideTexWithoutCurlies
	 */
	public function testInCurlies( $arg, string $expected ) {
		$n = new TexNode( $arg );
		$this->assertSame( $expected, $n->inCurlies() );
	}

	public function testExtractIdentifiers() {
		$n = new TexNode( new TexNode( 'a' ) );
		$this->assertEquals( [ 'a' ], $n->extractIdentifiers(), 'Should extract identifiers' );
	}

	public function testGetters() {
		$n = new TexNode( new TexNode( 'a' ) );
		$this->assertNotEmpty( $n->getArgs() );
	}

	public function testIdentiferMods() {
		$n = new TexNode( '' );
		$this->assertEquals( [], $n->getModIdent(),
			'Should contain a method stub for extracting identifier modifications' );
	}

	public function testExtractSubscripts() {
		$n = new TexNode( '' );
		$this->assertEquals( [], $n->extractSubscripts(),
			'Should contain a method stub for extracting subscripts' );
	}

	public function providNegativeMatches() {
		return [
			[ 'asd', 'sda' ],
			[ [ 'asd', 'ert' ], 'sda' ],
			[ [ 0 => 'not a string key' ], '0' ],
		];
	}

	/**
	 * @dataProvider providNegativeMatches
	 */
	public function testMatchFails( $target, string $str ) {
		$this->assertFalse( TexNode::match( $target, $str ) );
	}

	public function providPositiveMatches() {
		return [
			[ '', '' ],
			[ 'asd', 'asd' ],
			[ [ 'ert', 'asd' ], 'asd' ],
			[ [ 'asd' => 'key should match' ], 'asd' ],
			[ '0', '0' ],
			[ [ '0' ], '0' ],
			[ [ [ '0' ] ], '0' ],
		];
	}

	/**
	 * @dataProvider providPositiveMatches
	 */
	public function testMatchSucceeds( $target, string $str ) {
		$this->assertSame( $str, TexNode::match( $target, $str ) );
	}

	public function provideTextContainingFunctions() {
		return [
			[ '\\operatorname', '\\operatorname {someword}' ],
			[
				[ '\\operatorname', '\\nonexistingooperator' ],
				'\\operatorname {someword}',
				'\\operatorname'
			],
			[ '\\mbox', '\\mbox{\\somefunc}' ],
		];
	}

	/**
	 * @dataProvider provideTextContainingFunctions
	 */
	public function testContainsFunc( $target, string $t, string $expected = null ) {
		$this->assertSame( $expected ?? $target, TexNode::texContainsFunc( $target, $t ) );
	}

}
