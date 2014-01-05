<?php

/**
 * Test the LaTeXML output format.
 *
 * @group Math
 */
class MathLaTeXMLTest extends MediaWikiTestCase {

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
	protected function setUp() {
		global $wgMathValidModes;
		$wgMathValidModes[]=MW_MATH_LATEXML;
		parent::setUp();
	}
	/**
	 * Test rendering the string '0' see
	 * https://trac.mathweb.org/LaTeXML/ticket/1752
	 */
	public function testSpecialCase0() {
		global $wgMathFastDisplay;
		$wgMathFastDisplay = false;
		//FIXME:
		$this->markTestSkipped( "Bug in LaTeXML" );
		$renderer = MathRenderer::getRenderer( '0', array( ), MW_MATH_LATEXML );
		$expected = '0</cn>';
		$this->assertTrue( $renderer->render() );
		$this->assertContains( $expected, $renderer->getHtmlOutput(), 'Rendering the String "0"' );
	}

	/**
	 * Test rendering the string '0' see
	 * https://trac.mathweb.org/LaTeXML/ticket/1752
	 */
	public function testSpecialCaseText() {
		global $wgMathFastDisplay;
		if ( wfGetDB( DB_MASTER )->getType() === 'sqlite' ) {
			$this->markTestSkipped( "SQLite has global indices. We cannot " .
					"create the `unitest_math` table, its math_inputhash index " .
					"would conflict with the one from the real `math` table."
			);
		}

		$wgMathFastDisplay = false;
		$renderer = MathRenderer::getRenderer( 'x^2+\text{a sample Text}', array( ), MW_MATH_LATEXML );
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

		$renderer = $this->getMockBuilder( 'MathLaTeXML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$requestReturn = $renderer->makeRequest( $url, 'a+b', $res, $error
				, 'LaTeXMLHttpRequestTester' );
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
		$renderer = $this->getMockBuilder( 'MathLaTeXML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$requestReturn = $renderer->makeRequest( $url, 'a+b', $res, $error
				, 'LaTeXMLHttpRequestTester' );
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
		$renderer = $this->getMockBuilder( 'MathLaTeXML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$requestReturn = $renderer->makeRequest( $url, '$\longcommand$', $res
				, $error, 'LaTeXMLHttpRequestTester' );
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
		$renderer = $this->getMockBuilder( 'MathLaTeXML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$validSample = '<math>content</math>';
		$invalidSample = '<notmath />';
		$this->assertTrue( $renderer->isValidMathML( $validSample ), 'test if math expression is valid mathml sample' );
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ), 'test if math expression is invalid mathml sample' );
	}

	/**
	 * Tests the serialiazation of the LaTeXML settings
	 * @covers MathMathML::serializeSettings
	 */
	public function testSerializeSettings() {
		$renderer = $this->getMockBuilder( 'MathLaTeXML' )
				->setMethods( NULL )
				->disableOriginalConstructor()
				->getMock();
		$sampleSettings = array(
			'k1' => 'v1',
			'k2&=' => 'v2 + & *üö',
			'k3' => array(
				'v3A', 'v3b'
		) );
		$expected = 'k1=v1&k2%26%3D=v2+%2B+%26+%2A%C3%BC%C3%B6&k3=v3A&k3=v3b';
		$this->assertEquals( $expected, $renderer->serializeSettings( $sampleSettings ), 'test serialization of array settings' );
		$this->assertEquals( $expected, $renderer->serializeSettings( $expected ), 'test serialization of a string setting' );
	}

	/**
	 * Checks the basic functionallity
	 * i.e. if the span element is generated right.
	 */
	public function testIntegration() {
		global $wgMathLaTeXMLTimeout;
		global $wgMathFastDisplay;
		$wgMathFastDisplay = false;
		$wgMathLaTeXMLTimeout = 20;
		$renderer = MathRenderer::getRenderer( "a+b", array( ), MW_MATH_LATEXML );
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
class LaTeXMLHttpRequestTester {

	public static function factory() {
		return new LaTeXMLHttpRequestTester();
	}

	public static function execute() {
		return new LaTeXMLTestStatus();
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
class LaTeXMLTestStatus {

	static function isGood() {
		return MathLaTeXMLTest::$good;
	}

	static function hasMessage( $s ) {
		if ( $s == 'http-timed-out' ) {
			return MathLaTeXMLTest::$timeout;
		} else {
			return false;
		}
	}

	static function getHtml() {
		return MathLaTeXMLTest::$html;
	}

}