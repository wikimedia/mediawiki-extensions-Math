<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil
 */
class MMLutilTest extends MediaWikiUnitTestCase {

	public function testRound2em() {
		$this->assertSame( '0.278em', MMLutil::round2em( 5 / 18 ) );
		$this->assertSame( '-0.167em', MMLutil::round2em( -3 / 18 ) );
		$this->assertSame( '1.2em', MMLutil::round2em( 1.2 ) );
	}

	public static function provideDimen2em(): array {
		return [
			[ '5mu', '0.278em' ],
			[ '-3mu', '-0.167em' ],
			[ '18mu', '1em' ],
			[ '2 em', '2em' ],
			[ '5pt', null ],
		];
	}

	/**
	 * @dataProvider provideDimen2em
	 */
	public function testDimen2em( string $dimen, ?string $expected ) {
		$this->assertSame( $expected, MMLutil::dimen2em( $dimen ) );
	}
}
