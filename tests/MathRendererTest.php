<?php
/**
 * Test the database access and core functionality of MathRenderer.
 *
 * @group Math
 */
class MathRendererTest extends MediaWikiTestCase {
	const SOME_TEX = "a+b";
	const TEXVCCHECK_INPUT = '\forall \epsilon \exist \delta';
	const TEXVCCHECK_OUTPUT = '\forall \epsilon \exists \delta '; // be aware of the s at exists
	/**
	 * Checks the tex and hash functions
	 * @covers MathRenderer::getTex()
	 * @covers MathRenderer::__construct()
	 */
	public function testBasics() {
		$renderer = $this->getMockForAbstractClass( 'MathRenderer', array( self::SOME_TEX ) );
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
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
					'writeToDatabase',
					'render',
					'getMathTableName',
					'getHtmlOutput'
				) )->disableOriginalConstructor()->getMock();
		$renderer->expects( $this->never() )->method( 'writeToDatabase' );
		$renderer->writeCache();
	}

	/**
	 * Test behavior of writeCache() when values were changed.
	 * @covers MathRenderer::writeCache()
	 */
	public function testWriteCache() {
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
					'writeToDatabase',
					'render',
					'getMathTableName',
					'getHtmlOutput'
				) )->disableOriginalConstructor()->getMock();
		$renderer->expects( $this->never() )->method( 'writeToDatabase' );
		$renderer->writeCache();
	}

	public function testSetPurge() {
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
					'render',
					'getMathTableName',
					'getHtmlOutput'
				) )->disableOriginalConstructor()->getMock();
		$renderer->setPurge();
		$this->assertEquals( $renderer->isPurge(), true, "Test purge." );

	}

	public function testCheckingAlways() {
		$this->setMwGlobals( "wgMathDisableTexFilter", MW_MATH_CHECK_ALWAYS );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				) )->setConstructorArgs( array( self::TEXVCCHECK_INPUT ) )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromDatabase' );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		$this->assertEquals( $renderer->checkTex(), true );
		// now setTex sould not be called again
		$this->assertEquals( $renderer->checkTex(), true );

	}

	public function testCheckingNever() {
		$this->setMwGlobals( "wgMathDisableTexFilter", MW_MATH_CHECK_NEVER );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				) )->setConstructorArgs( array( self::TEXVCCHECK_INPUT ) )->getMock();
		$renderer->expects( $this->never() )->method( 'readFromDatabase' );
		$renderer->expects( $this->never() )->method( 'setTex' );

		$this->assertEquals( $renderer->checkTex(), true );
	}

	public function testCheckingNewUnknown() {
		$this->setMwGlobals( "wgMathDisableTexFilter", MW_MATH_CHECK_NEW );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				) )->setConstructorArgs( array( self::TEXVCCHECK_INPUT ) )->getMock();
		$renderer->expects( $this->once() )->method( 'readFromDatabase' )
			->will( $this->returnValue( false ) );
		$renderer->expects( $this->once() )->method( 'setTex' )->with( self::TEXVCCHECK_OUTPUT );

		$this->assertEquals( $renderer->checkTex(), true );
		// now setTex sould not be called again
		$this->assertEquals( $renderer->checkTex(), true );
	}

	public function testCheckingNewKnown() {
		$this->setMwGlobals( "wgMathDisableTexFilter", MW_MATH_CHECK_NEW );
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
					'render',
					'getMathTableName',
					'getHtmlOutput',
					'readFromDatabase',
					'setTex'
				) )->setConstructorArgs( array( self::TEXVCCHECK_INPUT ) )->getMock();
		$renderer->expects( $this->exactly( 2 ) )->method( 'readFromDatabase' )
			->will( $this->returnValue( true ) );
		$renderer->expects( $this->never() )->method( 'setTex' );

		$this->assertEquals( $renderer->checkTex(), true );
		// we don't mark a object as checked even though we rely on the database cache
		// so readFromDatabase will be called again
		$this->assertEquals( $renderer->checkTex(), true );
	}

	public function testSetRenderingTime(){
		/** @var MathRenderer $renderer */
		$renderer =
			$this->getMockBuilder( 'MathRenderer' )->setMethods( array(
				'render',
				'getMathTableName',
				'getHtmlOutput',
				'readFromDatabase',
				'setTex'
			) )->setConstructorArgs( array( self::TEXVCCHECK_INPUT ) )->getMock();
		$renderer->setRenderingTime(1.234);
		$this->assertEquals( 1234, $renderer->getRenderingTime(), "Check time float input");
		$renderer->setRenderingTime(4321);
		$this->assertEquals( 4321, $renderer->getRenderingTime(), "Check time integer input");
	}
}