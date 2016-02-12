<?php

/**
 * @covers MathInputCheckTexvc
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathInputCheckTexvcTest extends MediaWikiTestCase {

	/**
	 * @var MathInputCheckTexvc
	 */
	protected $BadObject;
	protected $GoodObject;

	protected static $hasTexvccheck;
	protected static $texvccheckPath;

	public static function setUpBeforeClass() {
		global $wgMathTexvcCheckExecutable;

		if ( is_executable( $wgMathTexvcCheckExecutable ) ) {
			wfDebugLog( __CLASS__, " using build in texvccheck from "
				. "\$wgMathTexvcCheckExecutable = $wgMathTexvcCheckExecutable" );
			# Using build-in
			self::$hasTexvccheck = true;
			self::$texvccheckPath = $wgMathTexvcCheckExecutable;
		} else {
			# Attempt to compile
			wfDebugLog( __CLASS__, " compiling texvccheck..." );
			$cmd = 'cd ' . dirname( __DIR__ ) . '/texvccheck; make --always-make 2>&1';
			wfShellExec( $cmd, $retval );
			if ( $retval === 0 ) {
				self::$hasTexvccheck = true;
				self::$texvccheckPath = dirname( __DIR__ ) . '/texvccheck/texvccheck';
				wfDebugLog( __CLASS__, ' compiled texvccheck at ' . self::$texvccheckPath );
			} else {
				wfDebugLog( __CLASS__, ' ocaml not available or compilation of texvccheck failed' );
			}
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
			$this->markTestSkipped( "No texvccheck installed on server" );
		} else {
			$this->setMwGlobals( 'wgMathTexvcCheckExecutable',
				self::$texvccheckPath );
		}
	}

	/**
	 * This is not a real phpUnit test.
	 * It's just to discover whether setting default values dependent
	 * on the existence of executables influences the performance reaonably.
	 * The test is disabled by default. You can enable it via
	 * php .../tests/phpunit/phpunit.php --group Utility ...
	 * @group Utility
	 */
	public function testPerformanceIsExecutable() {
		global $wgMathTexvcCheckExecutable;
		/** @var int the number of runs used in that test */
		$numberOfRuns = 10;
		/** @var double the maximal average time accetable for a execution of is_executable in seconds*/
		$maxAvgTime = .001;
		$tstart = microtime( true );

		for ( $i = 1; $i <= $numberOfRuns; $i++ ) {
			is_executable( $wgMathTexvcCheckExecutable );
		}

		$time = microtime( true ) - $tstart;
		$this->assertTrue(
			$time < $maxAvgTime * $numberOfRuns,
			'function is_executable consumes too much time'
		);
	}

	/**
	 * @covers MathInputCheckTexvc::testGetError
	 */
	public function testGetError() {
		$this->assertNull( $this->GoodObject->getError() );
		$this->assertNull( $this->BadObject->getError() );
		$this->BadObject->isValid();
		$this->GoodObject->isValid();
		$this->assertNull( $this->GoodObject->getError() );
		$expectedMessage = wfMessage(
			'math_unknown_function', '\newcommand'
		)->inContentLanguage()->escaped();
		$this->assertContains( $expectedMessage, $this->BadObject->getError() );
	}

	/**
	 * @covers MathInputCheckTexvc::isValid
	 */
	public function testIsValid() {
		$this->assertFalse( $this->BadObject->isValid() );
		$this->assertTrue( $this->GoodObject->isValid() );
	}

	/**
	 * @covers MathInputCheckTexvc::getValidTex
	 */
	public function testGetValidTex() {
		$this->assertNull( $this->GoodObject->getValidTex() );
		$this->assertNull( $this->BadObject->getValidTex() );
		$this->BadObject->isValid();
		$this->GoodObject->isValid();
		$this->assertNull( $this->BadObject->getValidTex() );

		// Be aware of the additional brackets and spaces inserted here
		$this->assertEquals( $this->GoodObject->getValidTex(), "\\sin \\left({\\frac  12}x\\right)" );
	}

	/**
	 * Test corner cases of texvccheck conversion
	 * @covers MathInputCheckTexvc::getValidTex
	 */
	public function testGetValidTexCornerCases() {
		$Object = new MathInputCheckTexvc( '\reals' );
		$Object->isValid();
		$this->assertEquals( "\\mathbb{R} ", $Object->getValidTex() );
		$Object = new MathInputCheckTexvc( '\lbrack' ); // Bug: 54624
		$Object->isValid();
		$this->assertEquals( '\\lbrack ', $Object->getValidTex() );
	}

	/**
	 * Tests behavior of convertTexvcError
	 * The method was moved from MathTexvc to MathInputCheckTexvc
	 * @covers MathTexvc::convertTexvcError
	 */
	public function testConvertTexvcError() {
		$texvc = $this->getMockBuilder( 'MathInputCheckTexvc' )
			->setMethods( null )
			->disableOriginalConstructor()
			->getMock();

		$mathFailure = wfMessage( 'math_failure' )->inContentLanguage()->escaped();

		$actualLexing = $texvc->convertTexvcError( 'E' );
		$expectedLexing = wfMessage( 'math_lexing_error', '' )->inContentLanguage()->escaped();
		$this->assertContains(
			$mathFailure, $actualLexing, 'Lexing error contains general math failure message'
		);
		$this->assertContains(
			$expectedLexing, $actualLexing, 'Lexing error contains detailed error for lexing'
		);

		$actualSyntax = $texvc->convertTexvcError( 'S' );
		$expectedSyntax = wfMessage( 'math_syntax_error', '' )->inContentLanguage()->escaped();
		$this->assertContains(
			$mathFailure, $actualSyntax, 'Syntax error contains general math failure message'
		);
		$this->assertContains(
			$expectedSyntax, $actualSyntax, 'Syntax error contains detailed error for syntax'
		);

		$unknownFunction = 'figureEightIntegral';
		$actualUnknownFunction = $texvc->convertTexvcError( "F$unknownFunction" );
		$expectedUnknownFunction = wfMessage(
			'math_unknown_function', $unknownFunction
		)->inContentLanguage()->escaped();
		$this->assertContains( $mathFailure, $actualUnknownFunction,
			'Unknown function error contains general math failure message'
		);
		$this->assertContains( $expectedUnknownFunction, $actualUnknownFunction,
			'Unknown function error contains detailed error for unknown function'
		);

		$actualUnknownError = $texvc->convertTexvcError( 'Q' );
		$expectedUnknownError = wfMessage( 'math_unknown_error', '' )->inContentLanguage()->escaped();
		$this->assertContains(
			$mathFailure, $actualUnknownError, 'Unknown error contains general math failure message'
		);
		$this->assertContains( $expectedUnknownError, $actualUnknownError,
			'Unknown error contains detailed error for unknownError'
		);
	}
}
