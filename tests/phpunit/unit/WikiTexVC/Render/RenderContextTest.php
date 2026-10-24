<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\Render;

use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext
 */
class RenderContextTest extends MediaWikiUnitTestCase {

	private const D = RenderContext::DISPLAY;
	private const T = RenderContext::TEXT;
	private const S = RenderContext::SCRIPT;
	private const SS = RenderContext::SCRIPT_SCRIPT;
	private const C = RenderContext::CRAMPED;

	/**
	 * The styles of numerator, denominator, subscript and superscript in each style, D' for cramped D
	 * tex.web: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=13855-13859
	 */
	public static function provideRules(): array {
		$c = self::C;
		return [
			'D' => [ self::D, self::T, self::T + $c, self::S + $c, self::S ],
			"D'" => [ self::D + $c, self::T + $c, self::T + $c, self::S + $c, self::S + $c ],
			'T' => [ self::T, self::S, self::S + $c, self::S + $c, self::S ],
			"T'" => [ self::T + $c, self::S + $c, self::S + $c, self::S + $c, self::S + $c ],
			'S' => [ self::S, self::SS, self::SS + $c, self::SS + $c, self::SS ],
			"S'" => [ self::S + $c, self::SS + $c, self::SS + $c, self::SS + $c, self::SS + $c ],
			'SS' => [ self::SS, self::SS, self::SS + $c, self::SS + $c, self::SS ],
			"SS'" => [ self::SS + $c, self::SS + $c, self::SS + $c, self::SS + $c, self::SS + $c ],
		];
	}

	/**
	 * @dataProvider provideRules
	 */
	public function testNextStyle( int $style, int $num, int $denom, int $sub, int $sup ) {
		$this->assertSame( $num, RenderContext::nextStyle( $style, 'num' ), 'num' );
		$this->assertSame( $denom, RenderContext::nextStyle( $style, 'denom' ), 'denom' );
		$this->assertSame( $sub, RenderContext::nextStyle( $style, 'sub' ), 'sub' );
		$this->assertSame( $sup, RenderContext::nextStyle( $style, 'sup' ), 'sup' );
		$this->assertSame( $style, RenderContext::nextStyle( $style, null ), 'no rule' );
		$this->assertSame( self::T, RenderContext::nextStyle( $style, 'text' ), 'text' );
	}

	public static function provideMathMLStyles(): array {
		return [
			[ self::D, true, 0 ],
			[ self::T + self::C, false, 0 ],
			[ self::S, false, 1 ],
			[ self::SS + self::C, false, 2 ],
		];
	}

	/**
	 * @dataProvider provideMathMLStyles
	 */
	public function testMathMLStyle( int $style, bool $display, int $level ) {
		$ctx = new RenderContext( style: $style );
		$this->assertSame( $display, $ctx->isDisplayStyle() );
		$this->assertSame( $level, $ctx->getScriptLevel() );
	}

	public function testForArgumentInheritsContextButNotLinks() {
		$ctx = new RenderContext( style: self::T, inMatrix: true, inText: true, squashLiterals: true,
			inSideset: true, intentParams: [ 'a' ], colorDefinitions: [ 'c' => [] ], negated: true,
			namedFunction: true, dotsVariant: 'dotsb', limitsBase: new Literal( '\\sum' ), beforeRight: true );
		$arg = $ctx->forArgument( self::S, [ 'mathvariant' => 'bold' ] );
		$this->assertSame( self::S, $arg->style );
		$this->assertSame( [ 'mathvariant' => 'bold' ], $arg->font );
		$this->assertTrue( $arg->inMatrix );
		$this->assertTrue( $arg->inText );
		$this->assertTrue( $arg->inSideset );
		$this->assertSame( [ 'a' ], $arg->intentParams );
		$this->assertSame( [ 'c' => [] ], $arg->colorDefinitions );
		$this->assertFalse( $arg->squashLiterals );
		$this->assertFalse( $arg->negated );
		$this->assertFalse( $arg->namedFunction );
		$this->assertNull( $arg->dotsVariant );
		$this->assertNull( $arg->limitsBase );
		$this->assertFalse( $arg->beforeRight );
	}

	public function testForArgumentTakesLinksAndOverrides() {
		$arg = ( new RenderContext() )->forArgument( self::D, [], inMatrix: true, squashLiterals: true,
			links: [ 'negated' => true, 'dotsVariant' => 'dotsi' ], beforeRight: true );
		$this->assertTrue( $arg->inMatrix );
		$this->assertTrue( $arg->squashLiterals );
		$this->assertTrue( $arg->negated );
		$this->assertSame( 'dotsi', $arg->dotsVariant );
		$this->assertTrue( $arg->beforeRight );
	}

	public function testForGroupMemberIsSharedAndHasNoLinks() {
		$ctx = new RenderContext( style: self::S, squashLiterals: true, negated: true, beforeRight: true );
		$member = $ctx->forGroupMember();
		$this->assertSame( $member, $ctx->forGroupMember() );
		$this->assertSame( self::S, $member->style );
		$this->assertTrue( $member->squashLiterals );
		$this->assertFalse( $member->negated );
		$this->assertFalse( $member->beforeRight );
	}

	public function testContextWithoutLinksIsItsGroupMember() {
		$ctx = new RenderContext( style: self::S, squashLiterals: true );
		$this->assertSame( $ctx, $ctx->forGroupMember() );
	}

	public function testHasFunctionArgument() {
		$this->assertTrue( ( new RenderContext( namedFunction: true, namedFunctionHasArgument: true ) )
			->hasFunctionArgument() );
		$this->assertFalse( ( new RenderContext( namedFunction: true ) )->hasFunctionArgument() );
		$this->assertFalse( ( new RenderContext( namedFunctionHasArgument: true ) )->hasFunctionArgument() );
	}
}
