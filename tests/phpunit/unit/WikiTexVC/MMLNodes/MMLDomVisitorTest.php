<?php

use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLDomVisitor;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;

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
	public function testNotImplemented() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [ 'mathvariant' => 'bold' ], 'x' );
		// not implemented
		$this->assertNull( $visitor->visit( $mi ) );
	}

}
