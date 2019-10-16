<?php

use DataValues\StringValue;
use DataValues\NumberValue;

/**
 * @covers MathValidator
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathValidatorTest extends MediaWikiTestCase {
	const VADLID_TEX = "a^2+b^2=c^2";
	const INVADLID_TEX = "\\notExists";

	protected static $hasRestbase;

	public static function setUpBeforeClass() {
		$rbi = new MathRestbaseInterface();
		self::$hasRestbase = $rbi->checkBackend( true );
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() : void {
		parent::setUp();

		if ( !self::$hasRestbase ) {
			$this->markTestSkipped( "Can not connect to Restbase Math interface." );
		}
	}

	protected function tearDown() : void {
		parent::tearDown();
	}

	public function testNotStringValue() {
		$validator = new MathValidator();
		$this->expectException( InvalidArgumentException::class );
		$validator->validate( new NumberValue( 0 ) );
	}

	public function testNullValue() {
		$validator = new MathValidator();
		$this->expectException( InvalidArgumentException::class );
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
