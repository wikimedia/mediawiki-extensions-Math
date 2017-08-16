<?php

/**
 * @covers MathInputCheckTexvc
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathoidCliTest extends MediaWikiTestCase {
	private $goodInput = '\sin\left(\frac12x\right)';
	private $badInput = '\newcommand{\text{do evil things}}';
	protected static $hasMathoidCli;

	public static function setUpBeforeClass() {
		global $wgMathoidCli;
		if ( is_array( $wgMathoidCli ) && is_executable( $wgMathoidCli[0] ) ) {
			self::$hasMathoidCli = true;
		}
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
		if ( !self::$hasMathoidCli ) {
			$this->markTestSkipped( "No mathoid cli configured on server" );
		}
	}

	public function testGood() {
		$mml = new MathMathML( $this->goodInput );
		$this->assertTrue( $mml->render(), 'assert that renders' );
		$this->assertContains('</mo>', $mml->getHtmlOutput() );
	}

	public function testBad() {
		$mml = new MathMathML( $this->badInput );
		$this->assertFalse( $mml->render(), 'assert that renders' );
		$this->assertContains('newcommand', $mml->getLastError() );
	}

}
