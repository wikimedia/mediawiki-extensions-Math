<?php

namespace phpunit\unit\WikiTexVC\MMLNodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtd;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtr;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtr
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MMLmtrTest extends MediaWikiUnitTestCase {
	public function testConstructor() {
		$mtd = new MMLmtd( '', [] );
		$mtr = new MMLmtr( '', [ 'mathvariant' => Variants::BOLD ], $mtd, $mtd );

		$this->assertEquals( 'mtr', $mtr->getName() );
		$this->assertEquals( [ 'mathvariant' => Variants::BOLD ], $mtr->getAttributes() );
		$this->assertEquals( $mtr->getChildren(), [ $mtd, $mtd ] );
	}
}
