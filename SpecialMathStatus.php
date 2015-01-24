<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2015 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * @author Moritz Schubotz
 */
class SpecialMathStatus extends SpecialPage {

	public function __construct( $name = 'MathStatus' ) {
		parent::__construct( $name );
	}

	/**
	 * @param null|string $query
	 *
	 * @throws MWException
	 * @throws PermissionsError
	 */
	function execute( $query ) {
		$this->setHeaders();
		if ( ! ( $this->getUser()->isAllowed( 'purge' ) ) ) {
			// The effect of loading this page is comparable to purge a page.
			// If desired a dedicated right e.g. "viewmathstatus" could be used instead.
			throw new PermissionsError( 'purge' );
		}

		$out = $this->getOutput();
		$out->addWikiMsg( 'math-status-introduction' );
		$enabledMathModes = MathHooks::getMathNames();
		foreach ( $enabledMathModes as $modeNr => $modeName ){
			$out->addWikiText( "* $modeName" );
			switch( $modeNr ){
				case MW_MATH_MATHML:
					$this->runMathMLTest( $modeName );
					break;
				case MW_MATH_LATEXML:
					$this->runMathLaTeXMLTest( $modeName );
			}
		}
	}

	private function runMathMLTest( $modeName ) {
		$this->getOutput()->addWikiMsgArray( 'math-test-start', $modeName );
		$this->testSpecialCaseText();
		$this->testMathMLIntegration();
		$this->testPmmlInput();
		$this->getOutput()->addWikiMsgArray( 'math-test-end', $modeName );
	}

	private function runMathLaTeXMLTest( $modeName ) {
		$this->getOutput()->addWikiMsgArray( 'math-test-start', $modeName );
		$this->testMathMLIntegration();
		$this->getOutput()->addWikiMsgArray( 'math-test-end', $modeName );
	}

	public function testSpecialCaseText() {
		$renderer = MathRenderer::getRenderer( 'x^2+\text{a sample Text}', array(), MW_MATH_MATHML );
		$expected = 'a sample Text</mtext>';
		$this->assertTrue( $renderer->render(), 'Rendering the input "x^2+\text{a sample Text}"'  );
		$this->assertContains( $expected, $renderer->getHtmlOutput(), 'Comparing to the reference rendering' );
	}

	/**
	 * Checks the basic functionality
	 * i.e. if the span element is generated right.
	 */
	public function testMathMLIntegration() {
		$svgRef = '<?xml version="1.0" standalone="no"?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg xmlns:xlink="http://www.w3.org/1999/xlink" style="vertical-align: -0.333ex; " width="5.167ex" height="1.833ex" viewBox="0 -717.9 2195.4 823.9" xmlns="http://www.w3.org/2000/svg" role="math" aria-labelledby="MathJax-SVG-1-Title MathJax-SVG-1-Desc">
<title id="MathJax-SVG-1-Title">Equation</title>
<desc id="MathJax-SVG-1-Desc">a plus b</desc>
<defs aria-hidden="true">
<path stroke-width="10" id="E1-MJMATHI-61" d="M33 157Q33 258 109 349T280 441Q331 441 370 392Q386 422 416 422Q429 422 439 414T449 394Q449 381 412 234T374 68Q374 43 381 35T402 26Q411 27 422 35Q443 55 463 131Q469 151 473 152Q475 153 483 153H487Q506 153 506 144Q506 138 501 117T481 63T449 13Q436 0 417 -8Q409 -10 393 -10Q359 -10 336 5T306 36L300 51Q299 52 296 50Q294 48 292 46Q233 -10 172 -10Q117 -10 75 30T33 157ZM351 328Q351 334 346 350T323 385T277 405Q242 405 210 374T160 293Q131 214 119 129Q119 126 119 118T118 106Q118 61 136 44T179 26Q217 26 254 59T298 110Q300 114 325 217T351 328Z"></path>
<path stroke-width="10" id="E1-MJMAIN-2B" d="M56 237T56 250T70 270H369V420L370 570Q380 583 389 583Q402 583 409 568V270H707Q722 262 722 250T707 230H409V-68Q401 -82 391 -82H389H387Q375 -82 369 -68V230H70Q56 237 56 250Z"></path>
<path stroke-width="10" id="E1-MJMATHI-62" d="M73 647Q73 657 77 670T89 683Q90 683 161 688T234 694Q246 694 246 685T212 542Q204 508 195 472T180 418L176 399Q176 396 182 402Q231 442 283 442Q345 442 383 396T422 280Q422 169 343 79T173 -11Q123 -11 82 27T40 150V159Q40 180 48 217T97 414Q147 611 147 623T109 637Q104 637 101 637H96Q86 637 83 637T76 640T73 647ZM336 325V331Q336 405 275 405Q258 405 240 397T207 376T181 352T163 330L157 322L136 236Q114 150 114 114Q114 66 138 42Q154 26 178 26Q211 26 245 58Q270 81 285 114T318 219Q336 291 336 325Z"></path>
</defs>
<g stroke="currentColor" fill="currentColor" stroke-width="0" transform="matrix(1 0 0 -1 0 0)" aria-hidden="true">
 <use xlink:href="#E1-MJMATHI-61" x="0" y="0"></use>
 <use xlink:href="#E1-MJMAIN-2B" x="756" y="0"></use>
 <use xlink:href="#E1-MJMATHI-62" x="1761" y="0"></use>
</g>
</svg>';
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_MATHML );
		$this->assertTrue( $renderer->render( true ), "Rendering of a+b in plain MathML mode" );
		$real = str_replace( "\n", '', $renderer->getHtmlOutput() );
		$expected = '<mo>+</mo>';
		$this->assertContains( $expected, $real, "Checking the presence of '+' in the MathML output" );
		$this->assertEquals( $svgRef, $renderer->getSvg(), "Comparing the generated SVG with the reference" );
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
		$this->assertEquals( 'pmml', $renderer->getInputType(), 'Checking if MathML input is supported' );
		$this->assertTrue( $renderer->render(), 'Rendering Presentation MathML sample' );
		$real = MathRenderer::renderMath( $inputSample, $attribs, MW_MATH_MATHML );
		$expected = 'hash=5628b8248b79267ecac656102334d5e3&amp;mode=5';
		$this->assertContains( $expected, $real, 'Checking if the link to SVG image is correct' );
	}

	/**
	 * Checks the basic functionality
	 * i.e. if the span element is generated right.
	 */
	public function testLaTeXMLIntegration() {
		$renderer = MathRenderer::getRenderer( "a+b", array(), MW_MATH_LATEXML );
		$this->assertTrue( $renderer->render( true ), "Rendering of a+b in LaTeXML mode" );
		$expected = '<math xmlns="http://www.w3.org/1998/Math/MathML" id="p1.1.m1.1" class="ltx_Math" alttext="{\displaystyle a+b}"  xref="p1.1.m1.1.cmml"><semantics id="p1.1.m1.1a" xref="p1.1.m1.1.cmml"><mrow id="p1.1.m1.1.4" xref="p1.1.m1.1.4.cmml"><mi id="p1.1.m1.1.1" xref="p1.1.m1.1.1.cmml">a</mi><mo id="p1.1.m1.1.2" xref="p1.1.m1.1.2.cmml">+</mo><mi id="p1.1.m1.1.3" xref="p1.1.m1.1.3.cmml">b</mi></mrow><annotation-xml encoding="MathML-Content" id="p1.1.m1.1.cmml" xref="p1.1.m1.1"><apply id="p1.1.m1.1.4.cmml" xref="p1.1.m1.1.4"><plus id="p1.1.m1.1.2.cmml" xref="p1.1.m1.1.2"/><ci id="p1.1.m1.1.1.cmml" xref="p1.1.m1.1.1">a</ci><ci id="p1.1.m1.1.3.cmml" xref="p1.1.m1.1.3">b</ci></apply></annotation-xml><annotation encoding="application/x-tex" id="p1.1.m1.1b" xref="p1.1.m1.1.cmml">{\displaystyle a+b}</annotation></semantics></math>';
		$real = preg_replace( "/\n\s*/", '', $renderer->getHtmlOutput() );
		$this->assertContains( $expected, $real
			, "Comparing the output to the MathML reference rendering" .
			  $renderer->getLastError() );
	}

	private function assertTrue( $expression, $message = '' ) {
		if ( $expression ){
			$this->getOutput()->addWikiMsgArray( 'math-test-success' , $message );
		} else {
			$this->getOutput()->addWikiMsgArray( 'math-test-fail' , $message );
		}
	}

	private function assertContains( $expected, $real, $message = '' ) {
		$this->assertTrue( strpos( $real, $expected ) !== false, $message );
	}

	private function assertEquals( $expected, $real, $message = '' ) {
		$this->assertTrue( $expected == $real, $message );
	}
}