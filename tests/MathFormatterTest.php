<?php
/**
 * Test the results of MathFormatter
 *
 * @group Math
 */

use DataValues\StringValue;
use DataValues\NumberValue;

class MathFormatterTest extends MediaWikiTestCase {
	const SOME_TEX = "a^2+b^2=c^2";
	const FORMAT_PLAIN = 'text/plain';
	const FORMAT_HTML = 'text/html';
	const FORMAT_XWIKI = 'text/x-wiki';
	const FORMAT_UNKNOWN = 'unknown/unknown';
	const FORMAT_VALUE = "";


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
		$this->assertEquals( self::FORMAT_PLAIN, $formatter->getFormat(), 'test getFormat' );
	}

	/**
	 * @expectedException DataValues\IllegalValueException
	 */
	public function testNotStringValue() {
		$formatter = new MathFormatter( self::FORMAT_PLAIN );
		$formatter->format( new NumberValue( 0 ) );
	}

	/**
	 * @expectedException DataValues\IllegalValueException
	 */
	public function testNullValue() {
		$formatter = new MathFormatter( self::FORMAT_PLAIN );
		$formatter->format( null );
	}

	public function testUnknownFormat() {
		$formatUnknownExpetctedReturn = "<strong class='error texerror'>";
		$formatter = new MathFormatter( self::FORMAT_UNKNOWN );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertContains( $formatUnknownExpetctedReturn, $resultFormat,
			'Error message was not returned properly' );
	}

	public function testFormatPlain() {
		$formatter = new MathFormatter( self::FORMAT_PLAIN );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertEquals( self::SOME_TEX, $resultFormat,
			'Results should be equal' );

	}

	public function testFormatHtml() {
		$formatter = new MathFormatter( self::FORMAT_HTML );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertContains( '</math>', $resultFormat,
			'Result must contain math-tag' );
	}

	public function testFormatXWiki() {
		$tex = self::SOME_TEX;
		$formatter = new MathFormatter( self::FORMAT_XWIKI );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertEquals( "<math>$tex</math>", $resultFormat,
			'Tex wasn\'t properly wrapped' );

	}
}
