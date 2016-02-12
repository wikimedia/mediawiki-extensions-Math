<?php

use DataValues\StringValue;
use DataValues\NumberValue;

/**
 * Test the results of MathFormatter
 *
 * @covers MathFormatter
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathFormatterTest extends MediaWikiTestCase {

	const SOME_TEX = 'a^2+b^2=c^2';

	protected static $hasRestbase;

	public static function setUpBeforeClass() {
		$rbi = new MathRestbaseInterface();
		self::$hasRestbase = $rbi->checkBackend( true );
	}

	protected function setUp() {
		parent::setUp();

		if ( !self::$hasRestbase ) {
			$this->markTestSkipped( 'Can not connect to Restbase Math interface.' );
		}
	}

	/**
	 * Checks the
	 * @covers MathFormatter::__construct()
	 */
	public function testBasics() {
		$formatter = new MathFormatter( 'text/plain' );
		// check if the format input was corretly passed to the class
		$this->assertSame( 'text/plain', $formatter->getFormat(), 'test getFormat' );
	}

	/**
	 * @expectedException ValueFormatters\Exceptions\MismatchingDataValueTypeException
	 */
	public function testNotStringValue() {
		$formatter = new MathFormatter( 'text/plain' );
		$formatter->format( new NumberValue( 0 ) );
	}

	/**
	 * @expectedException ValueFormatters\Exceptions\MismatchingDataValueTypeException
	 */
	public function testNullValue() {
		$formatter = new MathFormatter( 'text/plain' );
		$formatter->format( null );
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testUnknownFormat() {
		new MathFormatter( 'unknown/unknown' );
	}

	public function testFormatPlain() {
		$formatter = new MathFormatter( 'text/plain' );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertSame( self::SOME_TEX, $resultFormat );
	}

	public function testFormatHtml() {
		$formatter = new MathFormatter( 'text/html' );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertContains( '</math>', $resultFormat, 'Result must contain math-tag' );
	}

	public function testFormatXWiki() {
		$tex = self::SOME_TEX;
		$formatter = new MathFormatter( 'text/x-wiki' );
		$value = new StringValue( self::SOME_TEX );
		$resultFormat = $formatter->format( $value );
		$this->assertSame( "<math>$tex</math>", $resultFormat, 'Tex wasn\'t properly wrapped' );
	}

}
