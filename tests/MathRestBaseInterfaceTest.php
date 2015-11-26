<?php

/**
 * Test the interface to access RestBase paths
 * /media/math/check/{type}
 * /media/math/render/{format}/{hash}
 *
 * @group Math
 */
class MathRestBaseInterfaceTest extends MediaWikiTestCase {
	protected static $hasRestBase;

	public static function setUpBeforeClass() {
		$rbi = new MathRestBaseInterface();
		self::$hasRestBase = $rbi->checkBackend();
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
		if ( !self::$hasRestBase ) {
			$this->markTestSkipped( "Can not connect to RestBase Math interface." );
		}
	}

	public function testSuccess() {
		$input = '\\sin x^2';
		$rbi = new MathRestBaseInterface( $input );
		$this->assertTrue( $rbi->checkTeX(), "Assuming that $input is valid input." );
		$this->assertTrue( $rbi->getSuccess(), "Assuming that $input is valid input." );
		$this->assertEquals( '\\sin x^{2}', $rbi->getCheckedTex() );
	}

	public function testFail() {
		$input = '\\sin\\newcommand';
		$rbi = new MathRestBaseInterface( $input );
		$this->assertFalse( $rbi->checkTeX(), "Assuming that $input is invalid input." );
		$this->assertFalse( $rbi->getSuccess(), "Assuming that $input is invalid input." );
		$this->assertEquals( '', $rbi->getCheckedTex() );
		$this->assertEquals( 'Illegal TeX function', $rbi->getError()->error->message );
	}
}
