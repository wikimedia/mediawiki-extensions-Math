<?php

use DataValues\NumberValue;
use DataValues\StringValue;

/**
 * @covers \MathValidator
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathValidatorTest extends MediaWikiTestCase {
	private const VADLID_TEX = "a^2+b^2=c^2";
	private const INVADLID_TEX = "\\notExists";

	protected static $hasRestbase;

	public static function setUpBeforeClass() : void {
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
		$this->assertInstanceOf( \ValueValidators\Result::class, $result );
		$this->assertTrue( $result->isValid() );
	}

	public function testInvalidInput() {
		$validator = new MathValidator();
		$result = $validator->validate( new StringValue( self::INVADLID_TEX ) );
		$this->assertInstanceOf( \ValueValidators\Result::class, $result );
		$this->assertFalse( $result->isValid() );
	}
}
