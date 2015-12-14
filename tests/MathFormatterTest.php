<?php
/**
 * Test the results of MathFormatter
 *
 * @group Math
 */

use DataValues\StringValue;

class MathFormatterTest extends MediaWikiTestCase {
	const SOME_TEX = "a^2+b^2=c^2";
	const FORMAT_PLAIN = 'text/plain';
	const FORMAT_HTML = 'text/html';
	const FORMAT_XWIKI = 'text/x-wiki';
	const FORMAT_UNKNOWN = 'text/unknown';
	const FORMAT_UNKNOWN_EXPECTED_RETURN = "<strong class='error texerror'>"
	. "Unknown format " . self::FORMAT_UNKNOWN
	. "</strong>";
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
		$this->assertEquals( self::FORMAT_PLAIN, $formatter->getFormat(), "test getFormat" );
	}

	public function testUnknownFormat() {
		$formatter = new MathFormatter( self::FORMAT_UNKNOWN );

		$value = new StringValue(
				self::SOME_TEX
		);

		$resultFormat = $formatter->format( $value );

		$this->assertEquals( self::FORMAT_UNKNOWN_EXPECTED_RETURN, $resultFormat,
				"Results should be equal" );
	}

	public function testNullValue() {

	}

	public function testFormatPlain() {
		$formatter = new MathFormatter( self::FORMAT_PLAIN );
	}

	public function testFormatHtml() {
		$formatter = new MathFormatter( self::FORMAT_HTML );
	}

	public function testFormatXWiki() {
		$formatter = new MathFormatter( self::FORMAT_XWIKI );
	}
}
