<?php

/**
 * Test the database access and core functionality of MathRenderer.
 *
 * @covers MathRenderer
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathRendererTest extends MediaWikiTestCase {
	const SOME_TEX = "a+b";
	const TEXVCCHECK_INPUT = '\forall \epsilon \exist \delta';
	const TEXVCCHECK_OUTPUT = '\forall \epsilon \exists \delta '; // be aware of the s at exists

	protected static $hasRestbase;

	public static function setUpBeforeClass() {
		$rbi = new MathRestbaseInterface();
		self::$hasRestbase = $rbi->checkBackend( true );
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
		if ( !self::$hasRestbase ) {
			$this->markTestSkipped( "Can not connect to Restbase Math interface." );
		}
	}

	/**
	 * Checks the tex and hash functions
	 * @covers MathRenderer::getTex()
	 * @covers MathRenderer::__construct()
	 */
	public function testBasics() {
		$renderer = $this->getMockForAbstractClass( 'MathRenderer', [ self::SOME_TEX ] );
		// check if the TeX input was corretly passed to the class
		$this->assertEquals( self::SOME_TEX, $renderer->getTex(), "test getTex" );
		$this->assertEquals( $renderer->isChanged(), false, "test if changed is initially false" );
	}

	/**
	 * Test behavior of writeCache() when nothing was changed
	 * @covers MathRenderer::writeCache()
	 */
	public function testWriteCacheSkip() {
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( [
					'writeToDatabase',
					'render',
					'getMathTableName',
					'getHtmlOutput'
				] )->disableOriginalConstructor()->getMock();
		$renderer->expects( $this->never() )->method( 'writeToDatabase' );
		$renderer->writeCache();
	}

	/**
	 * Test behavior of writeCache() when values were changed.
	 * @covers MathRenderer::writeCache()
	 */
	public function testWriteCache() {
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( [
					'writeToDatabase',
					'render',
					'getMathTableName',
					'getHtmlOutput'
				] )->disableOriginalConstructor()->getMock();
		$renderer->expects( $this->never() )->method( 'writeToDatabase' );
		$renderer->writeCache();
	}

	public function testSetPurge() {
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput'
				] )->disableOriginalConstructor()->getMock();
		$renderer->setPurge();
		$this->assertEquals( $renderer->isPurge(), true, "Test purge." );

	}

	public function testDisableCheckingAlways() {
		$this->setMwGlobals( "wgMathDisableTexFilter", 'never' );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromDatabase' );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		$this->assertEquals( $renderer->checkTeX(), true );
		// now setTex sould not be called again
		$this->assertEquals( $renderer->checkTeX(), true );

	}

	public function testDisableCheckingNever() {
		$this->setMwGlobals( "wgMathDisableTexFilter", 'always' );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromDatabase' );
		$renderer->expects( $this->never() )->method( 'setTex' );

		$this->assertEquals( $renderer->checkTeX(), true );
	}

	public function testCheckingNewUnknown() {
		$this->setMwGlobals( "wgMathDisableTexFilter", 'new' );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->once() )->method( 'readFromDatabase' )
			->will( $this->returnValue( false ) );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		$this->assertEquals( $renderer->checkTeX(), true );
		// now setTex sould not be called again
		$this->assertEquals( $renderer->checkTeX(), true );
	}

	public function testCheckingNewKnown() {
		$this->setMwGlobals( "wgMathDisableTexFilter", 'new' );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( [
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				] )->setConstructorArgs( [ self::TEXVCCHECK_INPUT ] )->getMock();
		$renderer->expects( $this->exactly( 2 ) )->method( 'readFromDatabase' )
			->will( $this->returnValue( true ) );
		$renderer->expects( $this->never() )->method( 'setTex' );

		$this->assertEquals( $renderer->checkTeX(), true );
		// we don't mark a object as checked even though we rely on the database cache
		// so readFromDatabase will be called again
		$this->assertEquals( $renderer->checkTeX(), true );
	}
}
