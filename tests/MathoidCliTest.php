<?php

/**
 * @covers MathInputCheckTexvc
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathoidCliTest extends MediaWikiTestCase {

	/**
	 * @var MathInputCheckTexvc
	 */
	protected $BadObject;
	protected $GoodObject;

	protected static $hasTexvccheck;
	protected static $texvccheckPath;

	public static function setUpBeforeClass() {
		global $wgMathoidCli;

		if ( is_executable( $wgMathoidCli ) ) {
			wfDebugLog( __CLASS__, " using build in mathoid cli from from "
				. "\$wgMathoidCli = $wgMathoidCli" );
			# Using build-in
			self::$hasTexvccheck = true;
			self::$texvccheckPath = $wgMathoidCli;
		}
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
		$this->BadObject = new MathInputCheckTexvc( '\newcommand{\text{do evil things}}' );
		$this->GoodObject = new MathInputCheckTexvc( '\sin\left(\frac12x\right)' );

		if ( ! self::$hasTexvccheck ) {
			$this->markTestSkipped( "No mathoid installed on server" );
		} else {
			$this->setMwGlobals( 'wgMathoidCli',
				self::$texvccheckPath );
		}
	}

}
