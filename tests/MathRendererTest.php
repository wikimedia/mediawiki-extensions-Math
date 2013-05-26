<?php
/**
 * Test the database access and core functionallity of MathRenderer.
 *
 * @group Math
 */
class MathRendererTest extends MediaWikiTestCase {
	/**
	 * Checks the tex and hash functions
	 * @covers MathRenderer::getTex()
	 * @covers MathRenderer::__construct()
	 */
	public function testBasics() {
		$renderer = $this->getMockForAbstractClass( 'MathRenderer'
			, array ( MathDatabaseTest::SOME_TEX ) );
		// check if the TeX input was corretly passed to the class
		$this->assertEquals( MathDatabaseTest::SOME_TEX, $renderer->getTex()
			, "test getTex" );
		$this->assertEquals( $renderer->isChanged(), false
			, "test if changed is initially false");
	}

	/**
	 * Test behavior of writeCache() when nothing was changed
	 * @covers MathRenderer::writeCache()
	 */
	public function testWriteCacheSkip() {
		$renderer = $this->getMockBuilder( 'MathRenderer' )
			->setMethods( array( 'writeToDatabase' , 'render' ) )
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
			->setMethods( array( 'writeToDatabase' , 'render' ) )
			->disableOriginalConstructor()
			->getMock();
		$renderer->expects( $this->never() )
			->method( 'writeToDatabase' );
		$renderer->writeCache();
	}

	/**
	 * Test behavior $change when the rendered hash was changed
	 * @covers MathRenderer::setHash()
	 */
	public function testChangeHash() {
		$renderer = $this->getMockBuilder( 'MathRenderer' )
		->setMethods( array( 'render' ) )
		->disableOriginalConstructor()
		->getMock();
		$this->assertEquals( $renderer->isChanged(), false
			, "test if changed is initially false");
		$renderer->setHash('0000');
		$this->assertEquals( $renderer->isChanged(), true
			, "assumes that changing a hash sets changed to true");
	}

	public function testSetPurge(){
		$renderer = $this->getMockBuilder( 'MathRenderer' )
		->setMethods( array( 'render' ) )
		->disableOriginalConstructor()
		->getMock();
		$renderer->setPurge();
		$this->assertEquals( $renderer->isPurge(), true, "Test purge." );

	}
}