<?php

use MediaWiki\Extension\Math\Hooks;
use MediaWiki\Extension\Math\MathConfig;

/**
 * @covers \MediaWiki\Extension\Math\Hooks
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathHooksTest extends MediaWikiTestCase {

	public function testMathModeToHash() {
		$default = 0;
		$testCases = [
			'png'    => 0,
			'source' => 3,
			'mathml' => 5,
			'latexml' => 7,
			'invalid' => $default
		];

		foreach ( $testCases as $input => $expected ) {
			$real = Hooks::mathModeToHashKey( $input, $default );
			$this->assertEquals( $expected, $real, "Conversion to hash key" );
		}
	}

	public function testGetMathNames() {
		$real = Hooks::getMathNames();
		$this->assertEquals( 'PNG images', $real[MathConfig::MODE_PNG] );
	}

}
