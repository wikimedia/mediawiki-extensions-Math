<?php

/**
 * Test the LaTeXML output format.
 *
 * @group Math
 */
class MathLaTeXMLTest extends MediaWikiTestCase {

	protected function setUp() {
		global $wgMathValidModes;
		$wgMathValidModes[] = MW_MATH_LATEXML;
		parent::setUp();
	}
	/**
	 * Test rendering the string '0' see
	 * https://trac.mathweb.org/LaTeXML/ticket/1752
	 */
	public function testSpecialCase0() {
		global $wgMathFastDisplay;
		if ( wfGetDB( DB_MASTER )->getType() === 'sqlite' ) {
			$this->markTestSkipped( "SQLite has global indices. We cannot " .
				"create the `unitest_math` table, its math_inputhash index " .
				"would conflict with the one from the real `math` table."
			);
		}
		$wgMathFastDisplay = false;
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