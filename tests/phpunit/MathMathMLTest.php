<?php

use MediaWiki\Extension\Math\MathNativeMML;

/**
 * @covers \MediaWiki\Extension\Math\MathMathML
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathMathMLTest extends MediaWikiIntegrationTestCase {

	public function testMathMLConstructorWithPmml() {
		$mml = new MathNativeMML( '<mo>sin</mo>', [ 'type' => 'pmml' ] );
		$this->assertSame( 'pmml', $mml->getInputType() );
		$this->assertSame( '<math><mo>sin</mo></math>', $mml->getMathml() );
	}

	public function testMathMLConstructorWithInvalidType() {
		$mml = new MathNativeMML( '<mo>sin</mo>', [ 'type' => 'invalid' ] );
		$this->assertSame( 'tex', $mml->getInputType() );
	}

	public function testChangeRootElemts() {
		$mml = new MathNativeMML( '<mo>sin</mo>', [ 'type' => 'invalid' ] );
		$mml->setAllowedRootElements( [ 'a', 'b' ] );
		$this->assertSame( [ 'a', 'b' ], $mml->getAllowedRootElements() );
	}

	public function testisValidMathML() {
		$renderer = new MathNativeMML();
		$validSample = '<math>content</math>';
		$invalidSample = '<notmath />';
		$this->assertTrue( $renderer->isValidMathML( $validSample ),
			'test if math expression is valid mathml sample' );
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
	}

	public function testInvalidXml() {
		$renderer = new MathNativeMML();
		$invalidSample = '<mat';
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
		$renderer->setXMLValidation( false );
		$this->assertTrue( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
	}
}
