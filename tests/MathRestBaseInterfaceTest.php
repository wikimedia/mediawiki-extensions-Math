<?php

/**
 * Test the interface to access Restbase paths
 * /media/math/check/{type}
 * /media/math/render/{format}/{hash}
 *
 * @covers MathRestbaseInterface
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathRestbaseInterfaceTest extends MediaWikiTestCase {
	protected static $hasRestbase;

	public static function setUpBeforeClass() {
		$rbi = new MathRestbaseInterface();
		self::$hasRestbase = $rbi->checkBackend( true );
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
		if ( !self::$hasRestbase ) {
			$this->markTestSkipped( "Can not connect to Restbase Math interface." );
		}
	}

	public function testConfig() {
		$rbi = new MathRestbaseInterface();
		$this->assertTrue( $rbi->checkBackend() );
	}

	public function testSuccess() {
		$input = '\\sin x^2';
		$rbi = new MathRestbaseInterface( $input );
		$this->assertTrue( $rbi->getSuccess(), "Assuming that $input is valid input." );
		$this->assertEquals( '\\sin x^{2}', $rbi->getCheckedTex() );
		$this->assertContains( '<mi>sin</mi>', $rbi->getMathML() );
		$url = $rbi->getFullSvgUrl();
		$req = MWHttpRequest::factory( $url );
		$status = $req->execute();
		$this->assertTrue( $status->isOK() );
		$this->assertContains( '</svg>', $req->getContent() );
	}

	public function testFail() {
		$input = '\\sin\\newcommand';
		$rbi = new MathRestbaseInterface( $input );
		$this->assertFalse( $rbi->getSuccess(), "Assuming that $input is invalid input." );
		$this->assertEquals( '', $rbi->getCheckedTex() );
		$this->assertEquals( 'Illegal TeX function', $rbi->getError()->error->message );
	}

	public function testChem() {
		$input = '\ce{H2O}';
		$rbi = new MathRestbaseInterface( $input, 'chem' );
		$this->assertTrue( $rbi->checkTeX(), "Assuming that $input is valid input." );
		$this->assertTrue( $rbi->getSuccess(), "Assuming that $input is valid input." );
		$this->assertEquals( '{\ce {H2O}}', $rbi->getCheckedTex() );
		$this->assertContains( '<msubsup>', $rbi->getMathML() );
		$this->assertContains( '<mtext>H</mtext>', $rbi->getMathML() );
	}
	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage TeX input is invalid.
	 */
	public function testException() {
		$input = '\\newcommand';
		$rbi = new MathRestbaseInterface( $input );
		$rbi->getMathML();
	}

	/**
	 * @expectedException MWException
	 * @expectedExceptionMessage TeX input is invalid.
	 */
	public function testExceptionSvg() {
		$input = '\\newcommand';
		$rbi = new MathRestbaseInterface( $input );
		$rbi->getFullSvgUrl();
	}

}
