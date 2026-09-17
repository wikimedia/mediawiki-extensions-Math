<?php

use MediaWiki\Extension\Math\MathRenderer;
use MediaWiki\Extension\Math\Tests\MathMockHttpTrait;

/**
 * @covers \MediaWiki\Extension\Math\MathRenderer
 * @group Math
 * @license GPL-2.0-or-later
 */
class MathRendererTest extends MediaWikiIntegrationTestCase {
	use MathMockHttpTrait;

	private const SOME_TEX = "a+b";
	private const TEXVCCHECK_INPUT = '\sin x^2';
	private const TEXVCCHECK_OUTPUT = '\sin x^{2}';

	/**
	 * Checks the tex and hash functions
	 */
	public function testBasics() {
		$renderer = $this->getMockForAbstractClass( MathRenderer::class, [ self::SOME_TEX ] );
		/** @var MathRenderer $renderer */
		// check if the TeX input was corretly passed to the class
		$this->assertEquals( self::SOME_TEX, $renderer->getTex(), "test getTex" );
		$this->assertFalse( $renderer->isChanged(), "test if changed is initially false" );
	}

	/**
	 * Nothing was changed
	 */
	public function testWriteCacheSkip() {
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'dbOutArray',
					'render',
					'getHtmlOutput'
				] )->getMock();
		$renderer->expects( $this->never() )->method( 'dbOutArray' );
		/** @var MathRenderer $renderer */
		$renderer->writeCache();
	}

	/**
	 * Value was changed
	 */
	public function testWriteCache() {
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'dbOutArray',
					'render',
					'getHtmlOutput'
				] )->getMock();
		$renderer->expects( $this->once() )->method( 'dbOutArray' );
		/** @var MathRenderer $renderer */
		$renderer->setPurge();
		$renderer->writeCache();
	}

	public function testSetPurge() {
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getHtmlOutput'
				] )->getMock();
		/** @var MathRenderer $renderer */
		$renderer->setPurge();
		$this->assertTrue( $renderer->isPurge(), "Test purge." );
	}

	public function testDisableCheckingAlways() {
		$this->setupGoodMathRestBaseMockHttp();

		$this->overrideConfigValue( 'MathDisableTexFilter', 'never' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getHtmlOutput',
				'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromCache' );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
		// now setTex should not be called again
		$this->assertTrue( $renderer->checkTeX() );
	}

	public function testDisableCheckingNever() {
		$this->overrideConfigValue( 'MathDisableTexFilter', 'always' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getHtmlOutput',
				'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromCache' );
		$renderer->expects( $this->never() )->method( 'setTex' );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
	}

	public function testCheckingNewUnknown() {
		$this->setupGoodMathRestBaseMockHttp();

		$this->overrideConfigValue( 'MathDisableTexFilter', 'new' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getHtmlOutput',
					'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->once() )->method( 'readFromCache' )
			->willReturn( false );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
		// now setTex should not be called again
		$this->assertTrue( $renderer->checkTeX() );
	}

	public function testCheckingNewKnown() {
		$this->setupGoodMathRestBaseMockHttp();

		$this->overrideConfigValue( 'MathDisableTexFilter', 'new' );
		$renderer =
			$this->getMockBuilder( MathRenderer::class )->onlyMethods( [
					'render',
					'getHtmlOutput',
					'readFromCache',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->once() )->method( 'readFromCache' )
			->willReturn( true );
		$renderer->expects( $this->never() )->method( 'setTex' );

		/** @var MathRenderer $renderer */
		$this->assertTrue( $renderer->checkTeX() );
		// we don't mark a object as checked even though we rely on the database cache
		// so readFromDatabase will be called again
		$this->assertTrue( $renderer->checkTeX() );
	}
}
