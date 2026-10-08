<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLnodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MMLmoTest extends MediaWikiUnitTestCase {
	public function testConstructor() {
		$mo = new MMLmo( '', [ 'mathvariant' => Variants::BOLD ], '+' );
		$this->assertEquals( "mo", $mo->getName() );
		$this->assertEquals( "+", $mo->getText() );
	}

	public static function provideMovableLimits() {
		return [
			'large operator' => [ '∑', [], 'false' ],
			'as entity' => [ '&#x2211;', [], 'false' ],
			'explicit value is kept' => [ '∑', [ 'movablelimits' => 'true' ], 'true' ],
			'other operator' => [ '+', [], null ],
		];
	}

	/**
	 * @dataProvider provideMovableLimits
	 */
	public function testMovableLimits( string $text, array $attributes, ?string $expected ) {
		$mo = new MMLmo( '', $attributes, $text );
		$this->assertSame( $expected, $mo->getAttributes()['movablelimits'] ?? null );
	}
}
