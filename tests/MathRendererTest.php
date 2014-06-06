<?php
/**
 * Test the database access and core functionality of MathRenderer.
 *
 * @group Math
 */
class MathRendererTest extends MediaWikiTestCase {
	const SOME_TEX = "a+b";
	/**
	 * Checks the tex and hash functions
	 * @covers MathRenderer::getTex()
	 * @covers MathRenderer::__construct()
	 */
	public function testBasics() {
		$renderer = $this->getMockForAbstractClass( 'MathRenderer'
			, array ( self::SOME_TEX ) );
		// check if the TeX input was corretly passed to the class
		$this->assertEquals( self::SOME_TEX, $renderer->getTex()
			, "test getTex" );
		$this->assertEquals( $renderer->isChanged(), false
			, "test if changed is initially false" );
	}

	/**
	 * Test behavior of writeCache() when nothing was changed
	 * @covers MathRenderer::writeCache()
	 */
	public function testWriteCacheSkip() {
		$renderer = $this->getMockBuilder( 'MathRenderer' )
			->setMethods( array( 'writeToDatabase' , 'render', 'getMathTableName', 'getHtmlOutput' ) )
			->disableOriginalConstructor()
			->getMock();
		$renderer->expects( $this->never() )
			->method( 'writeToDatabase' );
		$renderer->writeCache();
	}

	/**
	 * Test behavior of writeCache() when values were changed.
	 * @covers MathRenderer::writeCache()
	 */
	public function testWriteCache() {
		$renderer = $this->getMockBuilder( 'MathRenderer' )
			->setMethods( array( 'writeToDatabase' , 'render', 'getMathTableName', 'getHtmlOutput' ) )
			->disableOriginalConstructor()
			->getMock();
		$renderer->expects( $this->never() )
			->method( 'writeToDatabase' );
		$renderer->writeCache();
	}

	public function testSetPurge() {
		$renderer = $this->getMockBuilder( 'MathRenderer' )
			->setMethods( array( 'render', 'getMathTableName', 'getHtmlOutput' ) )
			->disableOriginalConstructor()
			->getMock();
		$renderer->setPurge();
		$this->assertEquals( $renderer->isPurge(), true, "Test purge." );

	}
}