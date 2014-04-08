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
		$errmsg = wfMessage( 'math_latexml_invalidresponse' , $url, '' )
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
	 * @covers MathLaTeXML::makeRequest
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

	/**
	 * Checks if a String is a valid MathML element
	 * @covers MathLaTeXML::isValidXML
	 */
	public function testisValidXML() {
		$validSample = '<math>content</math>';
		$invalidSample = '<notmath />';
		$renderer = $this->getMockBuilder( 'MathLaTeXML' )
			->setMethods( NULL ) // avoid mocking any methods
			->disableOriginalConstructor()
			->getMock();
		$this->assertTrue(
			$renderer->isValidMathML( $validSample ),
			'test if math expression is valid mathml sample'
		);
		$this->assertFalse(
			$renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample'
		);
	}

	/**
	 * Tests the serialiazation of the LaTeXML settings
	 * @covers MathLaTeXML::serializeSettings
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
		$this->assertEquals(
			$expected,
			$renderer->serializeSettings( $sampleSettings ),
			'test serialization of array settings'
		);
		$this->assertEquals(
			$expected,
			$renderer->serializeSettings( $expected ),
			'test serialization of a string setting'
		);
	}
	/**
	 * Checks the basic functionality
	 * i.e. if the span element is generated right.
	 */
	public function testIntegration() {
		$this->setMwGlobals( 'wgMathLaTeXMLTimeout', 20 );
		$this->setMwGlobals( 'wgMathValidModes', array( MW_MATH_LATEXML ) );
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_LATEXML );
		$real = $renderer->render( true );
		$expected = '<span class="tex" dir="ltr" id="a_b"><math xmlns="http://www.w3.org/1998/Math/MathML" id="p1.1.m1" class="ltx_Math" alttext="{\displaystyle a+b}" display="inline" xml:id="p1.1.m1.1" xref="p1.1.m1.1.cmml">   <semantics xml:id="p1.1.m1.1a" xref="p1.1.m1.1.cmml">     <mrow xml:id="p1.1.m1.1.4" xref="p1.1.m1.1.4.cmml">       <mi xml:id="p1.1.m1.1.1" xref="p1.1.m1.1.1.cmml">a</mi>       <mo xml:id="p1.1.m1.1.2" xref="p1.1.m1.1.2.cmml">+</mo>       <mi xml:id="p1.1.m1.1.3" xref="p1.1.m1.1.3.cmml">b</mi>     </mrow>     <annotation-xml encoding="MathML-Content" xml:id="p1.1.m1.1.cmml" xref="p1.1.m1.1">       <apply xml:id="p1.1.m1.1.4.cmml" xref="p1.1.m1.1.4">         <plus xml:id="p1.1.m1.1.2.cmml" xref="p1.1.m1.1.2"/>         <ci xml:id="p1.1.m1.1.1.cmml" xref="p1.1.m1.1.1">a</ci>         <ci xml:id="p1.1.m1.1.3.cmml" xref="p1.1.m1.1.3">b</ci>       </apply>     </annotation-xml>     <annotation encoding="application/x-tex" xml:id="p1.1.m1.1b" xref="p1.1.m1.1.cmml">{\displaystyle a+b}</annotation>   </semantics> </math></span>';
		$this->assertEquals( $expected, $real
				, "Rendering of a+b in plain Text mode." .
				$renderer->getLastError() );
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
		return MathLaTeXMLTest::$content;
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
