<?php
/**
 * Test the results of MathFormatter
 *
 * @group Math
 */

use DataValues\StringValue;
use DataValues\NumberValue;

class MathValidatorTest extends MediaWikiTestCase {
	const VADLID_TEX = "a^2+b^2=c^2";
	const INVADLID_TEX = "\\notExists";


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
	 * @expectedException DataValues\IllegalValueException
	 */
	public function testNotStringValue() {
		$validator = new MathValidator();
		$validator->validate( new NumberValue( 0 ) );
	}

	/**
	 * @expectedException DataValues\IllegalValueException
	 */
	public function testNullValue() {
		$validator = new MathValidator();
		$validator->validate( null );
	}

	public function testValidInput() {
		$validator = new MathValidator();
		$result = $validator->validate( new StringValue( self::VADLID_TEX ) );
		// not supported by jenkins php version
		// $this->assertType( \ValueValidators\Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testInvalidInput() {
		$validator = new MathValidator();
		$result = $validator->validate( new StringValue( self::INVADLID_TEX ) );
		// not supported by jenkins php version
		// $this->assertType( \ValueValidators\Result::class, $result );
		$this->assertFalse( $result->isValid() );
	}
}
