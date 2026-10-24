<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Nodes;

use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Declaration;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\FQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2sq;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiUnitTestCase;
use RuntimeException;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode
 */
class TexNodeTest extends MediaWikiUnitTestCase {

	public static function provideTexToRender() {
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

	public static function provideTexWithoutCurlies() {
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

	public static function provideNegativeMatches() {
		return [
			[ 'asd', 'sda' ],
			[ [ 'asd', 'ert' ], 'sda' ],
			[ [ 0 => 'not a string key' ], '0' ],
		];
	}

	/**
	 * @dataProvider provideNegativeMatches
	 */
	public function testMatchFails( $target, string $str ) {
		$this->assertFalse( TexNode::match( $target, $str ) );
	}

	public static function providePositiveMatches() {
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
	 * @dataProvider providePositiveMatches
	 */
	public function testMatchSucceeds( $target, string $str ) {
		$this->assertSame( $str, TexNode::match( $target, $str ) );
	}

	public static function provideTextContainingFunctions() {
		return [
			[ '', '', false ],
			[ '\\', '\\' ],
			[ 'bad input', 'bad input', false ],
			[ '\\operatorname', '\\mismatch', false ],
			[ '\\operatorname', '\\operatorname' ],
			[ '\\operatorname', '\\operatorname {}' ],
			[ '\\operatorname', '\\operatorname {someword}' ],
			[ '\\operatorname', '\\operatorname {someword}(' ],
			[ '\\operatorname', '\\operatorname {someword}[' ],
			[ '\\operatorname', '\\operatorname {someword}{', false ],
			[ '\\operatorname', '\\operatorname {someword}\\{' ],
			[ '\\operatorname', '\\operatorname {someword} ' ],
			[ '\\operatorname', '\\operatorname {someword}  ', false ],
			[ '\\operatorname', '\\operatorname {\\someword}', false ],
			[ '\\operatorname', '\\operatorname{someword}', false ],
			[
				[ '\\operatorname', '\\nonexistingooperator' ],
				'\\operatorname {someword}',
				'\\operatorname'
			],
			[ '\\mbox', '\\mbox{}', false ],
			[ '\\mbox', '\\mbox{foo}', false ],
			[ '\\mbox', '\\mbox{\\}' ],
			[ '\\mbox', '\\mbox{\\somefunc}' ],
			[ '\\somefunc', '\\mbox{\\somefunc}' ],
			[ '\\mismatch', '\\mbox{\\somefunc}', false ],
			[ '\\somefunc', '\\mbox {\\somefunc}', false ],
			[ '\\color', '\\color' ],
			[ '\\color', '\\color the rest is ignored' ],
			[ '\\pagecolor', '\\pagecolor' ],
			[ '\\definecolor', '\\definecolor' ],
			[ '\\mathbb', '\\mathbb {}', false ],
			[ '\\mathbb', '\\mathbb {A}', false ],
			[ '\\mathbb', '\\mathbb {foo}', false ],
			[ '\\mathbb', '\\mathbb{}', false ],
			[ '\\mathbb', '\\mathbb {.}', false ],
			[ '\\mathbb', '\\mathbb {..........}', false ],
			[ '\\mathbb', '\\mathbb {\\foo}' ],
			[ '\\foo', '\\mathbb {\\foo}' ],
		];
	}

	/**
	 * @dataProvider provideTextContainingFunctions
	 */
	public function testContainsFunc( $target, string $t, $expected = null ) {
		$this->assertSame( $expected ?? $target, TexNode::texContainsFunc( $target, $t ) );
	}

	public function testIsEmptyString() {
		$this->assertTrue( ( new TexNode( '', '' ) )->isEmpty() );
		$this->assertFalse( ( new TexNode( '', 'a' ) )->isEmpty() );
		$this->assertTrue( ( new TexNode() )->isEmpty() );
	}

	public function testIsEmptyObject() {
		$this->assertTrue( ( new TexNode( new TexNode() ) )->isEmpty() );
		$this->assertFalse( ( new TexNode( new TexNode( 'a' ) ) )->isEmpty() );
		$this->assertTrue( ( new TexNode( new TexArray() ) )->isEmpty() );
		$this->assertTrue( ( new TexNode( new TexArray( new TexNode( '' ) ) ) )->isEmpty() );
	}

	/**
	 * tex.web: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=13568-13572
	 * swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=13855-13859
	 */
	public static function provideStyles(): array {
		$fraction = static fn ( $name ) => new Fun2( $name, new Literal( 'a' ), new Literal( 'b' ) );
		$scripts = static fn () => new FQ( new Literal( '\\sum' ), new Literal( 'a' ), new Literal( 'b' ) );
		$root = static fn () => new Fun2sq( '\\sqrt', new Literal( 'n' ), new Literal( 'x' ) );
		return [
			'numerator in display' => [ $fraction( '\\frac' ), '\\displaystyle', 'getArg1', false, 0 ],
			'numerator in text' => [ $fraction( '\\frac' ), '\\textstyle', 'getArg1', false, 1 ],
			'denominator in scriptscript' => [ $fraction( '\\frac' ), '\\scriptscriptstyle', 'getArg2', false, 2 ],
			'\\dfrac numerator in text' => [ $fraction( '\\dfrac' ), '\\textstyle', 'getArg1', false, 0 ],
			'base in display' => [ $scripts(), '\\displaystyle', 'getBase', true, 0 ],
			'subscript in display' => [ $scripts(), '\\displaystyle', 'getDown', false, 1 ],
			'superscript in script' => [ $scripts(), '\\scriptstyle', 'getUp', false, 2 ],
			'root index' => [ $root(), '\\displaystyle', 'getArg1', false, 2 ],
			'radicand' => [ $root(), '\\displaystyle', 'getArg2', true, 0 ],
		];
	}

	/**
	 * @dataProvider provideStyles
	 */
	public function testStyleOfArguments(
		TexNode $node, string $declaration, string $getter, bool $display, int $level
	) {
		$group = new TexArray( $node );
		$declaration = new Declaration( $declaration, $group );
		$groupCtx = $declaration->argContext( $group, new RenderContext() );
		$ctx = $node->argContext( $node->$getter(), $group->argContext( $node, $groupCtx ) );
		$this->assertSame( $display, $ctx->isDisplayStyle() );
		$this->assertSame( $level, $ctx->getScriptLevel() );
	}

	public function testDeclarationHoldsToTheEndOfTheGroup() {
		$group = ( new TexVC() )->check( 'a\\textstyle b c' )['input'];
		$ctx = new RenderContext();
		$this->assertTrue( $group->argContext( $group[0], $ctx )->isDisplayStyle() );
		$this->assertInstanceOf( Declaration::class, $group[1] );
		$argCtx = $group[1]->argContext( $group[1]->getArg(), $group->argContext( $group[1], $ctx ) );
		$this->assertFalse( $group[1]->getArg()->argContext( $group[1]->getArg()[1], $argCtx )->isDisplayStyle() );
	}
}
