<?php
/**
* Test the LaTeXML output format.
*
* @group Math
*/
class MathLaTeXMLTest extends MediaWikiTestCase {

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * @covers MathTexvc::makeRequest
	 */
	function testMakeRequest() {
		global $wgLaTeXMLTimeout;
		// TODO: How to mock HTTP::post()
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_LATEXML );

		$real = $renderer->makeRequest( 'http://www.google.com'
			, 'a+b', $res, $error );
		$this->assertEquals( false, $real, "bad call" );
		$this->assertEquals( false, $res, "bad call" );
		$errmsg = wfMessage( 'math_latexml_noresponse' )->inContentLanguage()->escaped();
		$this->assertContains($errmsg, $error, "bad call" );

		$real = $renderer->makeRequest( 'http://latexml.mathweb.org/convert'
			, 'a+b', $res, $error );
		$this->assertEquals( true, $real, "successfull call return" );
		$this->isTrue( $res, "successfull call" );
		$this->assertNull( $error, "successfull call errormessage" );

		$wgLaTeXMLTimeout = 1;
		$real = $renderer->makeRequest( 'http://latexml.mathweb.org/convert'
			, '$\renewcommand\foo{\foo}\foo$', $res, $error );
		$this->assertEquals( false, $real, "timeout call return" );
		$this->assertEquals( false, $res, "timeout call return" );
		$errmsg = wfMessage( 'math_latexml_timeout' )->inContentLanguage()->escaped();
		$this->assertContains($errmsg, $error, "timeout call errormessage" );

		$wgLaTeXMLTimeout = .000000001; //jenkins is very fast or caches $wg* PROBLEM!
		$real = $renderer->makeRequest( 'http://latexml.mathweb.org/convert'
				, '$a+b$', $res, $error );
		$this->assertEquals( false, $real, "timeout call return" );
		$this->assertEquals( false, $res, "timeout call return" );
		$errmsg = wfMessage( 'math_latexml_timeout' )->inContentLanguage()->escaped();
		$this->assertContains($errmsg, $error, "timeout call errormessage" );
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