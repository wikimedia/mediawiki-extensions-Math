<?php
/**
 * Test the results of MathFormatter
 *
 * @group Math
 */

class MathFormatterTest extends MediaWikiTestCase {
	const SOME_TEX = "a^2+b^2=c^2";
	const FORMAT_PLAIN = "text/plain";

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
	}

	/**
	 * Checks the
	 * @covers MathFormatter::__construct()
	 */
	public function testBasics() {
		$formatter = $this->getMockForAbstractClass( 'MathFormatter', array( self::FORMAT_PLAIN ) );
		// check if the format input was corretly passed to the class
		$this->assertEquals( self::FORMAT_PLAIN, $formatter->getFormat(), "test getFormat" );
	}

	public function testFormat() {
		$formatter = $this->getMockBuilder( 'MathFormatter' )
				->setMethods( array( 'format' ) )
				->setConstructorArgs( array( self::FORMAT_PLAIN ) )
				->getMock();
		$formatReturn = $formatter->format( array( 'value' => 'a^2+b^2=c^2' ) );
		$this->assertEquals( self::SOME_TEX, $formatReturn,
				"formatReturn is plain text if input format is text/plain" );
	}
}
