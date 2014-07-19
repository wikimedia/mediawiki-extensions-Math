<?php
/**
 * Test the MathML output format.
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
		$renderer = MathRenderer::getRenderer( 'x^2+\text{a sample Text}', array(), MW_MATH_MATHML );
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
		$errmsg = wfMessage( 'math_invalidresponse', '', $url, '' )->inContentLanguage()->escaped();
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
		$errmsg = wfMessage( 'math_timeout', '', $url )->inContentLanguage()->escaped();
		$this->assertContains( $errmsg, $error, "timeout call errormessage" );
	}


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
	 * Checks the basic functionality
	 * i.e. if the span element is generated right.
	 */
	public function testIntegration() {
		global $wgMathMathMLTimeout;
		$svgRef = '<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns:xlink="http://www.w3.org/1999/xlink" style="vertical-align: -0.333ex; margin-left: 0ex; margin-right: 0ex; margin: 1px 0px;" width="5.167ex" height="1.833ex" viewBox="0 -717.9 2195.4 823.9" xmlns="http://www.w3.org/2000/svg">
<defs>
<path stroke-width="10" id="E1-MJMATHI-61" d="M33 157Q33 258 109 349T280 441Q331 441 370 392Q386 422 416 422Q429 422 439 414T449 394Q449 381 412 234T374 68Q374 43 381 35T402 26Q411 27 422 35Q443 55 463 131Q469 151 473 152Q475 153 483 153H487Q506 153 506 144Q506 138 501 117T481 63T449 13Q436 0 417 -8Q409 -10 393 -10Q359 -10 336 5T306 36L300 51Q299 52 296 50Q294 48 292 46Q233 -10 172 -10Q117 -10 75 30T33 157ZM351 328Q351 334 346 350T323 385T277 405Q242 405 210 374T160 293Q131 214 119 129Q119 126 119 118T118 106Q118 61 136 44T179 26Q217 26 254 59T298 110Q300 114 325 217T351 328Z"></path>
<path stroke-width="10" id="E1-MJMAIN-2B" d="M56 237T56 250T70 270H369V420L370 570Q380 583 389 583Q402 583 409 568V270H707Q722 262 722 250T707 230H409V-68Q401 -82 391 -82H389H387Q375 -82 369 -68V230H70Q56 237 56 250Z"></path>
<path stroke-width="10" id="E1-MJMATHI-62" d="M73 647Q73 657 77 670T89 683Q90 683 161 688T234 694Q246 694 246 685T212 542Q204 508 195 472T180 418L176 399Q176 396 182 402Q231 442 283 442Q345 442 383 396T422 280Q422 169 343 79T173 -11Q123 -11 82 27T40 150V159Q40 180 48 217T97 414Q147 611 147 623T109 637Q104 637 101 637H96Q86 637 83 637T76 640T73 647ZM336 325V331Q336 405 275 405Q258 405 240 397T207 376T181 352T163 330L157 322L136 236Q114 150 114 114Q114 66 138 42Q154 26 178 26Q211 26 245 58Q270 81 285 114T318 219Q336 291 336 325Z"></path>
</defs>
<g stroke="black" fill="black" stroke-width="0" transform="matrix(1 0 0 -1 0 0)">
 <use xlink:href="#E1-MJMATHI-61"></use>
 <use xlink:href="#E1-MJMAIN-2B" x="756" y="0"></use>
 <use xlink:href="#E1-MJMATHI-62" x="1761" y="0"></use>
</g>
</svg>';
		$wgMathMathMLTimeout = 20;
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_MATHML );
		$this->assertTrue( $renderer->render( true ) );
		$real = str_replace( "\n", '', $renderer->getHtmlOutput() );
		$expected = '<mo>+</mo>';
		$this->assertContains( $expected, $real, "Rendering of a+b in plain MathML mode" );
		$this->assertEquals( $svgRef, $renderer->getSvg() );
	}

	/**
	 * Checks the experimental option to 'render' MathML input
	 */
	public function testPmmlInput() {
		// sample from 'Navajo Coal Combustion and Respiratory Health Near Shiprock, New Mexico' in ''Journal of Environmental and Public Health'' , vol. 2010p.
		// authors  Joseph E. Bunnell;  Linda V. Garcia;  Jill M. Furst;  Harry Lerch;  Ricardo A. Olea;  Stephen E. Suitt;  Allan Kolker
		$inputSample = '<msub>  <mrow>  <mi> P</mi> </mrow>  <mrow>  <mi> i</mi>  <mi> j</mi> </mrow> </msub>  <mo> =</mo>  <mfrac>  <mrow>  <mn> 100</mn>  <msub>  <mrow>  <mi> d</mi> </mrow>  <mrow>  <mi> i</mi>  <mi> j</mi> </mrow> </msub> </mrow>  <mrow>  <mn> 6.75</mn>  <msub>  <mrow>  <mi> r</mi> </mrow>  <mrow>  <mi> j</mi> </mrow> </msub> </mrow> </mfrac>  <mo> ,</mo> </math>';
		$attribs = array( 'type' => 'pmml' );
		$renderer = new MathMathML( $inputSample, $attribs );
		$this->assertEquals( 'pmml', $renderer->getInputType(), 'Input type was not set correctly' );
		$this->assertTrue( $renderer->render(), 'Failed to render with error:' . $renderer->getLastError() );
		$real = MathRenderer::renderMath( $inputSample, $attribs, MW_MATH_MATHML );
		$expected = 'hash=5628b8248b79267ecac656102334d5e3&amp;mode=5';
		$this->assertContains( $expected, $real, 'Link to SVG image missing' );
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

	static function getWikiText() {
		return MathMathMLTest::$html;
	}

}