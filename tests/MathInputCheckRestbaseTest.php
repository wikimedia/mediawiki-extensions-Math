<?php

/**
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathInputCheckRestbaseTest extends MediaWikiTestCase {
	protected static $hasRestbase;
	/** @var MathInputCheckRestbase */
	protected $BadObject;
	/** @var MathInputCheckRestbase */
	protected $GoodObject;
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
		$this->BadObject = new MathInputCheckRestbase( '\newcommand{\text{do evil things}}' );
		$this->GoodObject = new MathInputCheckRestbase( '\sin\left(\frac12x\right)' );

	}

	/**
 * @covers MathInputCheckRestbase::getError
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
	 * @covers MathInputCheckRestbase::getError
	 */
	public function testErrorSyntax() {
		$o = new MathInputCheckRestbase( '\left(' );
		$this->assertFalse( $o->isValid() );
		$expectedMessage = wfMessage( 'math_syntax_error' )->inContentLanguage()->escaped();
		$this->assertContains( $expectedMessage, $o->getError() );
	}
	/**
	 * @covers MathInputCheckRestbase::getError
	 */
	public function testErrorLexing() {
		$o = new MathInputCheckRestbase( "\x61\xCC\x81" );
		$this->assertFalse( $o->isValid() );
		// Lexical errors are no longer supported. The new error message
		// Expected "-", "[", "\\\\",
		// "\\\\begin", "\\\\begin{", "]", "^", "_", "{", [ \\t\\n\\r], [%$], [().], [,:;?!\\\'],
		// [-+*=], [0-9], [><~], [\\/|] or [a-zA-Z] but "\\u0301" found.
		// is more expressive anyhow.
		$expectedMessage = wfMessage( 'math_syntax_error' )->inContentLanguage()->escaped();
		$this->assertContains( $expectedMessage, $o->getError() );
	}

	/**
	 * @covers MathInputCheckRestbase::isValid
	 */
	public function testIsValid() {
		$this->assertFalse( $this->BadObject->isValid() );
		$this->assertTrue( $this->GoodObject->isValid() );
	}

	/**
	 * @covers MathInputCheckRestbase::getValidTex
	 */
	public function testGetValidTex() {
		$this->assertNull( $this->GoodObject->getValidTex() );
		$this->assertNull( $this->BadObject->getValidTex() );
		$this->BadObject->isValid();
		$this->GoodObject->isValid();
		$this->assertNull( $this->BadObject->getValidTex() );

		// Note that texvcjs has slightly diverged from texvc and enforces brackets for function
		// arguments. Also the double space between frac and the arg has ben reduce to a single space.
		$this->assertEquals( $this->GoodObject->getValidTex(), '\\sin \\left({\\frac {1}{2}}x\\right)' );
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

}
