<?php

/**
 * @covers MathInputCheckTexvc
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
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
		$mml = new MathMathMLCli( $this->goodInput );
		$input = [ 'good' => [ $mml ] ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertTrue( $mml->render(), 'assert that renders' );
		$this->assertContains( '</mo>', $mml->getMathml() );
	}

	public function testUndefinedFunctionError() {
		$mml = new MathMathMLCli( $this->badInput );
		$input = [ 'bad' => [ $mml ] ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that fails' );
		$this->assertContains( 'newcommand', $mml->getLastError() );
	}

	public function testSyntaxError() {
		$mml = new MathMathMLCli( '^' );
		$input = [ 'bad' => [ $mml ] ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that fails' );
		$this->assertContains( 'SyntaxError', $mml->getLastError() );
	}

	public function testCeError() {
		$mml = new MathMathMLCli( '\ce{H2O}' );
		$input = [ 'bad' => [ $mml ] ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that fails' );
		$this->assertContains( 'SyntaxError', $mml->getLastError() );
	}

	public function testEmpty() {
		$mml = new MathMathMLCli( '' );
		$input = [ 'bad' => [ $mml ] ];
		MathMathMLCli::batchEvaluate( $input );
		$this->assertFalse( $mml->render(), 'assert that renders' );
		$this->assertFalse( $mml->isTexSecure() );
		$this->assertContains( 'empty', $mml->getLastError() );
	}

}
