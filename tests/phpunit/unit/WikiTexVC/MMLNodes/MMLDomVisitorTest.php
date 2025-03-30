<?php

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLDomVisitor;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmn;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;

/**
 * Test the results of MathFormatter
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLDomVisitor
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MMLDomVisitorTest extends MediaWikiUnitTestCase {

	public function testLeafNodeConversion() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [ 'mathvariant' => 'bold' ], 'x' );
		$visitor->visit( $mi );
		$this->assertEquals(
			'<mi mathvariant="bold">x</mi>',
			$visitor->getHTML()
		);
	}

	public function testEmptyContainerConversion() {
		$visitor = new MMLDomVisitor();
		$mrow = new MMLmrow();
		$visitor->visit( $mrow );
		$this->assertEquals(
			'<mrow data-mjx-texclass="ORD"></mrow>',
			$visitor->getHTML()
		);
	}

	public function testStringCastLeafNode() {
		$visitor = new MMLDomVisitor();
		$mn = new MMLmn( '', [], '5' );
		$mn->accept( $visitor );
		$this->assertEquals(
			'<mn>5</mn>',
			$visitor->getHTML()
		);
	}

	public function testSpecialCharacterEscaping() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [], '<>&"' );
		$mi->accept( $visitor );
		$this->assertEquals(
			'<mi>&lt;&gt;&amp;"</mi>',
			$visitor->getHTML()
		);
	}
}
