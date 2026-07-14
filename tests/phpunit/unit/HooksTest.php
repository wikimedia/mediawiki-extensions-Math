<?php
namespace MediaWiki\Extension\Math\Tests;

use MediaWiki\Extension\Math\Hooks;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Settings\Config\ArrayConfigBuilder;
use MediaWiki\Settings\Config\PhpIniSink;
use MediaWiki\Settings\SettingsBuilder;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\Hooks
 */
class HooksTest extends MediaWikiUnitTestCase {

	public static function provideOnConfig() {
		$defaults = [
			'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
			'MathInternalRestbaseURL' => null,
		];

		yield 'defaults' => [
			$defaults,
			[
				'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
				'MathInternalRestbaseURL' => 'https://wikimedia.org/api/rest_',
			]
		];

		yield 'explicit MathFullRestbaseURL' => [
			[
				'MathFullRestbaseURL' => 'https://mywiki.test/rest/'
			] + $defaults,
			[
				'MathFullRestbaseURL' => 'https://mywiki.test/rest/',
				'MathInternalRestbaseURL' => 'https://mywiki.test/rest/',
			]
		];

		yield 'explicit MathInternalRestbaseURL' => [
			[
				'MathInternalRestbaseURL' => 'https://localhost:12345/rest/',
			] + $defaults,
			[
				'MathFullRestbaseURL' => 'https://wikimedia.org/api/rest_',
				'MathInternalRestbaseURL' => 'https://localhost:12345/rest/',
			]
		];
	}

	/**
	 * @dataProvider provideOnConfig
	 */
	public function testOnConfig( array $config, array $expected ) {
		$configSink = new ArrayConfigBuilder();
		$configSink->setMulti( $config );

		$settings = new SettingsBuilder(
			__DIR__,
			$this->createNoOpMock( ExtensionRegistry::class ),
			$configSink,
			$this->createNoOpMock( PhpIniSink::class )
		);

		Hooks::onConfig( [], $settings );

		$actual = $settings->getConfig();
		foreach ( $expected as $name => $value ) {
			$this->assertSame( $value, $actual->get( $name ) );
		}
	}

}
