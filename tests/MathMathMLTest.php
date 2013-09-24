<?php

/**
 * Test the LaTeXML output format.
 *
 * @group Math
 */
class MathMathMLTest extends MediaWikiTestCase {

	// State-variables for HTTP Mockup classes
	public static $content = null;
	public static $good = false;
	public static $html = false;
	public static $timeout = false;

	/**
	 * Set the mock values for the HTTP Mockup classes
	 *
	 * @param boolean $good
	 * @param mixed $html HTML of the error message or false if no error is present.
	 * @param boolean $timeout true if
	 */
	public static function setMockValues( $good, $html, $timeout ) {
		self::$good = $good;
		self::$html = $html;
		self::$timeout = $timeout;
	}


	/**
	 * Test rendering the string '0' see
	 * https://trac.mathweb.org/LaTeXML/ticket/1752
	 */
	public function testSpecialCaseText() {
		$this->markTestSkipped( 'currently no live svgtex server availible' );
		if ( wfGetDB( DB_MASTER )->getType() === 'sqlite' ) {
			$this->markTestSkipped( "SQLite has global indices. We cannot " .
					"create the `unitest_math` table, its math_inputhash index " .
					"would conflict with the one from the real `math` table."
			);
		}

		$renderer = MathRenderer::getRenderer( 'x^2+\text{a sample Text}', array( ), MW_MATH_MATHML );
		$expected = 'a sample Text</mtext>';
		$this->assertTrue( $renderer->render() );
		$this->assertContains( $expected, $renderer->getHtmlOutput(), 'Rendering the String "\text{CR}"' );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Invalid request.
	 * @covers MathTexvc::makeRequest
	 */
	public function testMakeRequestInvalid() {
		self::setMockValues( false, false, false );
		$url = 'http://example.com/invalid';

		$renderer = $this->getMockBuilder( 'MathMathML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$requestReturn = $renderer->makeRequest( $url, 'a+b', $res, $error
				, 'MathMLHttpRequestTester' );
		$this->assertEquals( false, $requestReturn
				, "requestReturn is false if HTTP::post returns false." );
		$this->assertEquals( false, $res
				, "res is false if HTTP:post returns false." );
		$errmsg = wfMessage( 'math_latexml_invalidresponse', $url, '' )
						->inContentLanguage()->escaped();
		$this->assertContains( $errmsg, $error
				, "return an error if HTTP::post returns false" );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Valid request.
	 * @covers MathTexvc::makeRequest
	 */
	public function testMakeRequestSuccess() {
		self::setMockValues( true, true, false );
		$url = 'http://example.com/valid';
		$renderer = $this->getMockBuilder( 'MathMathML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$requestReturn = $renderer->makeRequest( $url, 'a+b', $res, $error
				, 'MathMLHttpRequestTester' );
		$this->assertEquals( true, $requestReturn, "successful call return" );
		$this->isTrue( $res, "successfull call" );
		$this->assertEquals( $error, '', "successfull call errormessage" );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Timeout.
	 * @covers MathMathML::makeRequest
	 */
	public function testMakeRequestTimeout() {
		self::setMockValues( false, true, true );
		$url = 'http://example.com/timeout';
		$renderer = $this->getMockBuilder( 'MathMathML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$requestReturn = $renderer->makeRequest( $url, '$\longcommand$', $res
				, $error, 'MathMLHttpRequestTester' );
		$this->assertEquals( false, $requestReturn, "timeout call return" );
		$this->assertEquals( false, $res, "timeout call return" );
		$errmsg = wfMessage( 'math_latexml_timeout', $url )
						->inContentLanguage()->escaped();
		$this->assertContains( $errmsg, $error, "timeout call errormessage" );
	}

//	public function testisValidXML() {
//		$validSample = '<math>content</math>';
//		$invalidSample = '<notmath />';
//		$this->assertTrue( MathMathML::isValidMathML( $validSample ), 'test if math expression is valid mathml sample' );
//		$this->assertFalse( MathMathML::isValidMathML( $invalidSample ), 'test if math expression is invalid mathml sample' );
//	}

	/**
	 * Checks if a String is a valid MathML element
	 * @covers MathMathML::isValidXML
	 */
	public function testisValidXML() {
		$renderer = $this->getMockBuilder( 'MathMathML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$validSample = '<math>content</math>';
		$invalidSample = '<notmath />';
		$this->assertTrue( $renderer->isValidMathML( $validSample ), 'test if math expression is valid mathml sample' );
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ), 'test if math expression is invalid mathml sample' );
	}


	/**
	 * Checks the basic functionallity
	 * i.e. if the span element is generated right.
	 */
	public function testIntegration() {
		global $wgMathMathMLTimeout;
		$this->markTestSkipped( 'currently no live svgtex server availible' );
		$wgMathMathMLTimeout = 20;
		$renderer = MathRenderer::getRenderer( "a+b", array( ), MW_MATH_MATHML );
		$this->assertTrue( $renderer->render( true ) );
		$real = str_replace( "\n", '', $renderer->getHtmlOutput() );
		$expected = '<plus';
		$this->assertContains( $expected, $real
				, "Rendering of a+b in plain Text mode" );
	}

}

/**
 * Helper class for testing
 * @author physikerwelt
 * @see MWHttpRequestTester
 *
 */
class MathMLHttpRequestTester {

	public static function factory() {
		return new MathMLHttpRequestTester();
	}

	public static function execute() {
		return new MathMLTestStatus();
	}

	public static function getContent() {
		return MathMathMLTest::$content;
	}

}

/**
 * Helper class for testing
 * @author physikerwelt
 * @see Status
 */
class MathMLTestStatus {

	static function isGood() {
		return MathMathMLTest::$good;
	}

	static function hasMessage( $s ) {
		if ( $s == 'http-timed-out' ) {
			return MathMathMLTest::$timeout;
		} else {
			return false;
		}
	}

	static function getHtml() {
		return MathMathMLTest::$html;
	}

}