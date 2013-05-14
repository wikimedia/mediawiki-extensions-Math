<?php
/**
* Test the LaTeXML output format.
*
* @group Math
*/
class MathLaTeXMLTest extends MediaWikiTestCase {
	public static $content = null;
	public static $good = false;
	public static $html = false;
	public static $message = false;

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * @covers MathTexvc::makeRequest
	 */
	function testMakeRequest() {
		// TODO: How to mock HTTP::post()
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_LATEXML );
		// self::$status=Status::newFatal("Http request that leads to an error");
		$requestReturn = $renderer->makeRequest( 'http://www.google.com'
			, 'a+b', $res, $error, 'LaTeXMLHttpRequestTester' );
		$this->assertEquals( false, $requestReturn, "requestReturn is false if HTTP::post returns false." );
		$this->assertEquals( false, $res, "res is false if HTTP:post returns false." );
		$errmsg = wfMessage( 'math_latexml_invalidresponse' )->inContentLanguage()->escaped();
		$this->assertContains( $errmsg, $error, "return an error if HTTP::post returns false" );

		self::$good = true;
		self::$content = true;
		$requestReturn = $renderer->makeRequest( 'http://latexml.mathweb.org/convert'
			, 'a+b', $res, $error, 'LaTeXMLHttpRequestTester' );
		$this->assertEquals( true, $requestReturn, "successful call return" );
		$this->isTrue( $res, "successfull call" );
		$this->assertNull( $error, "successfull call errormessage" );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * @covers MathTexvc::makeRequest
	 */
	function testTimeout() {
		self::$good = false;
		self::$content = true;
		self::$message = true;
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_LATEXML );
		$this->setMwGlobals( 'wgLaTeXMLTimeout', 5 );
		$requestReturn = $renderer->makeRequest( 'http://latexml.mathweb.org/convert'
				, '$\renewcommand\foo{\foo}\foo$', $res, $error , 'LaTeXMLHttpRequestTester' );
		$this->assertEquals( false, $requestReturn, "timeout call return" );
		$this->assertEquals( false, $res, "timeout call return" );
		$errmsg = wfMessage( 'math_latexml_timeout' )->inContentLanguage()->escaped();
		$this->assertContains( $errmsg, $error, "timeout call errormessage" );
	}

	/**
	 * Checks the basic functionallity
	 * i.e. if the span element is generated right.
	 */
	public function testIntegration() {
		global $wgLaTeXMLTimeout;
		$wgLaTeXMLTimeout = 20;
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_LATEXML );
		$real = $renderer->render( true );
		$expected = '<span class="tex" dir="ltr" id="a_b"><math xmlns="http://www.w3.org/1998/Math/MathML" id="p1.1.m1" class="ltx_Math" alttext="a+b" xml:id="p1.1.m1.1" display="inline" xref="p1.1.m1.1.cmml">   <semantics xml:id="p1.1.m1.1a" xref="p1.1.m1.1.cmml">     <mrow xml:id="p1.1.m1.1.4" xref="p1.1.m1.1.4.cmml">       <mi xml:id="p1.1.m1.1.1" xref="p1.1.m1.1.1.cmml">a</mi>       <mo xml:id="p1.1.m1.1.2" xref="p1.1.m1.1.2.cmml">+</mo>       <mi xml:id="p1.1.m1.1.3" xref="p1.1.m1.1.3.cmml">b</mi>     </mrow>     <annotation-xml xml:id="p1.1.m1.1.cmml" encoding="MathML-Content" xref="p1.1.m1.1">       <apply xml:id="p1.1.m1.1.4.cmml" xref="p1.1.m1.1.4">         <plus xml:id="p1.1.m1.1.2.cmml" xref="p1.1.m1.1.2"/>         <ci xml:id="p1.1.m1.1.1.cmml" xref="p1.1.m1.1.1">a</ci>         <ci xml:id="p1.1.m1.1.3.cmml" xref="p1.1.m1.1.3">b</ci>       </apply>     </annotation-xml>     <annotation xml:id="p1.1.m1.1b" encoding="application/x-tex" xref="p1.1.m1.1.cmml">a+b</annotation>   </semantics> </math></span>';
		$this->assertEquals( $expected, $real
				, "Rendering of a+b in plain Text mode" );
	}
}

/**
 * Helper classes for testing
 * @author physikerwelt
 *
 */
class LaTeXMLHttpRequestTester {
	public static function factory() {
		return new LaTeXMLTestRequest();
	}
}
class LaTeXMLTestRequest {
	public static function execute() {
		return new LaTeXMLTestStatus();
	}
	public static function getContent() {
		return MathLaTeXMLTest::$content;
	}
}
class LaTeXMLTestStatus {
	static function isGood() {
		return MathLaTeXMLTest::$good;
	}

	static function hasMessage( $s ) {
		if ( $s == 'http-timed-out' ) {
			return MathLaTeXMLTest::$message;
		} else {
			return false;
		}
	}
	static function getHtml() {
		return MathLaTeXMLTest::$html;
	}
}