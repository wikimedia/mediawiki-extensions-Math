<?php
/**
* Test the LaTeXML output format.
*
* @group Math
*/
class MathLaTeXMLTest extends MediaWikiTestCase {

	/**
	 * Tests the serialization of the LaTeXML settings
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
		$this->assertTrue( $renderer->render( true ) );
		$expected = '<math xmlns="http://www.w3.org/1998/Math/MathML" id="p1.1.m1.1" class="ltx_Math" alttext="{\displaystyle a+b}" ><semantics id="p1.1.m1.1a"><mrow id="p1.1.m1.1.4" xref="p1.1.m1.1.4.cmml"><mi id="p1.1.m1.1.1" xref="p1.1.m1.1.1.cmml">a</mi><mo id="p1.1.m1.1.2" xref="p1.1.m1.1.2.cmml">+</mo><mi id="p1.1.m1.1.3" xref="p1.1.m1.1.3.cmml">b</mi></mrow><annotation-xml encoding="MathML-Content" id="p1.1.m1.1b"><apply id="p1.1.m1.1.4.cmml" xref="p1.1.m1.1.4"><plus id="p1.1.m1.1.2.cmml" xref="p1.1.m1.1.2"/><ci id="p1.1.m1.1.1.cmml" xref="p1.1.m1.1.1">a</ci><ci id="p1.1.m1.1.3.cmml" xref="p1.1.m1.1.3">b</ci></apply></annotation-xml><annotation encoding="application/x-tex" id="p1.1.m1.1c">{\displaystyle a+b}</annotation></semantics></math>';
		$real = preg_replace( "/\n\s*/", '', $renderer->getHtmlOutput() );
		$this->assertContains( $expected, $real
			, "Rendering of a+b in plain Text mode." .
			$renderer->getLastError() );
	}
}
