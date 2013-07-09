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
	 * Test rendering the string '0' see 
	 * https://trac.mathweb.org/LaTeXML/ticket/1752
	 */
	public function testSpecialCase0(){
		$renderer = MathRenderer::getRenderer( "0", array(), MW_MATH_LATEXML );
		$expected = '<span class="tex" dir="ltr"><math xmlns="http://www.w3.org/1998/Math/MathML" id="p1.1.m1" class="ltx_Math" alttext="0" xml:id="p1.1.m1.1" display="inline" xref="p1.1.m1.1.cmml">   <semantics xml:id="p1.1.m1.1a" xref="p1.1.m1.1.cmml">     <mn xml:id="p1.1.m1.1.1" xref="p1.1.m1.1.1.cmml">0</mn>     <annotation-xml xml:id="p1.1.m1.1.cmml" encoding="MathML-Content" xref="p1.1.m1.1">       <cn type="integer" xml:id="p1.1.m1.1.1.cmml" xref="p1.1.m1.1.1">0</cn>     </annotation-xml>     <annotation xml:id="p1.1.m1.1b" encoding="application/x-tex" xref="p1.1.m1.1.cmml">0</annotation>   </semantics> </math></span>';
		$real = $renderer->render();
		$this->assertEquals( $expected, $real ,'Rendering the String "0"' );
	}

		/**
	 * Test rendering the string '0' see 
	 * https://trac.mathweb.org/LaTeXML/ticket/1752
	 */
	public function testSpecialCaseText(){
		//$this->markTestSkipped( "Bug in LaTeXML");
		$renderer = MathRenderer::getRenderer( "\text{CR}", array(), MW_MATH_LATEXML );
		$expected = '<span class="tex" dir="ltr" id=".09ext.7BCR.7D"><math xmlns="http://www.w3.org/1998/Math/MathML" id="p1.1.m1" class="ltx_Math" alttext="ext{CR}" xml:id="p1.1.m1.1" display="inline" xref="p1.1.m1.1.cmml">   <semantics xml:id="p1.1.m1.1a" xref="p1.1.m1.1.cmml">     <mrow xml:id="p1.1.m1.1.6" xref="p1.1.m1.1.6.cmml">       <mi xml:id="p1.1.m1.1.1" xref="p1.1.m1.1.1.cmml">e</mi>       <mo xml:id="p1.1.m1.1.6.1" xref="p1.1.m1.1.6.1.cmml">⁢</mo>       <mi xml:id="p1.1.m1.1.2" xref="p1.1.m1.1.2.cmml">x</mi>       <mo xml:id="p1.1.m1.1.6.1a" xref="p1.1.m1.1.6.1.cmml">⁢</mo>       <mi xml:id="p1.1.m1.1.3" xref="p1.1.m1.1.3.cmml">t</mi>       <mo xml:id="p1.1.m1.1.6.1b" xref="p1.1.m1.1.6.1.cmml">⁢</mo>       <mi xml:id="p1.1.m1.1.4" xref="p1.1.m1.1.4.cmml">C</mi>       <mo xml:id="p1.1.m1.1.6.1c" xref="p1.1.m1.1.6.1.cmml">⁢</mo>       <mi xml:id="p1.1.m1.1.5" xref="p1.1.m1.1.5.cmml">R</mi>     </mrow>     <annotation-xml xml:id="p1.1.m1.1.cmml" encoding="MathML-Content" xref="p1.1.m1.1">       <apply xml:id="p1.1.m1.1.6.cmml" xref="p1.1.m1.1.6">         <times xml:id="p1.1.m1.1.6.1.cmml" xref="p1.1.m1.1.6.1"/>         <ci xml:id="p1.1.m1.1.1.cmml" xref="p1.1.m1.1.1">e</ci>         <ci xml:id="p1.1.m1.1.2.cmml" xref="p1.1.m1.1.2">x</ci>         <ci xml:id="p1.1.m1.1.3.cmml" xref="p1.1.m1.1.3">t</ci>         <ci xml:id="p1.1.m1.1.4.cmml" xref="p1.1.m1.1.4">C</ci>         <ci xml:id="p1.1.m1.1.5.cmml" xref="p1.1.m1.1.5">R</ci>       </apply>     </annotation-xml>     <annotation xml:id="p1.1.m1.1b" encoding="application/x-tex" xref="p1.1.m1.1.cmml">ext{CR}</annotation>   </semantics> </math></span>';
		$real = $renderer->render();
		$this->assertEquals( $expected, $real ,'Rendering the String "\text{CR}"' );
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
		$errmsg = wfMessage( 'math_latexml_invalidresponse' , $url,'' )
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
		$this->assertEquals( $error,'', "successfull call errormessage" );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Timeout.
	 * @covers MathTexvc::makeRequest
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
	 * 
	 * see issue http://html5sec.org/#130
	 */
	public function testInsecureResult(){
		$bad_mml='<math href="javascript:alert(1)">CLICKME</math>';
		$bad_mml2='<math> <!-- up to FF 13 --> <maction actiontype="statusline#http://google.com" xlink:href="javascript:alert(2)">CLICKME</maction>  <!-- FF 14+ --> <maction actiontype="statusline" xlink:href="javascript:alert(3)">CLICKME<mtext>http://http://google.com</mtext></maction> </math>';
		//$final_mml=MathLaTeXML::embedMathML($bad_mml);
		//$plain_tex=MathRenderer::renderMath($bad_mml,array(),MW_MATH_SOURCE);
		//echo(var_dump(MathLaTeXML::isValidMathML($bad_mml))."\n");
		$renderer = new MathLaTeXML($bad_mml2);
		$this->assertFalse($renderer->isValidMathML($bad_mml2));
		//echo($final_mml);
		//echo($plain_tex);
		
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