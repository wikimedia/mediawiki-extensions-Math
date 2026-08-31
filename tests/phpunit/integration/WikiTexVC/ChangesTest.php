<?php
namespace MediaWiki\Extension\Math\WikiTexVC;

use MediaWiki\Config\Config;
use MediaWiki\Config\HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathReferenceData;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWikiIntegrationTestCase;

/**
 * Verifies native MathML output against the stored regression references.
 *
 * @covers \MediaWiki\Extension\Math\MathNativeMML
 * @covers \MediaWiki\Extension\Math\MathReferenceData
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil
 */
final class ChangesTest extends MediaWikiIntegrationTestCase {

	private MathConfig $mathConfig;
	private HookContainer $hookContainer;
	private Config $mainConfig;

	/**
	 * @dataProvider provideTestCases
	 */
	public function testChanges( string $hash, array $testCase ) {
		$this->assertSame( $hash, hash( MathReferenceData::HASH_ALGORITHM, $testCase['input'] ) );
		$expected = $testCase;
		MathReferenceData::renderReferenceEntry(
			$testCase,
			$this->mathConfig,
			$this->hookContainer,
			$this->mainConfig
		);
		$expectedOutputs = $expected['outputs'] ?? [ $expected ];
		$actualOutputs = $testCase['outputs'] ?? [ $testCase ];
		foreach ( $expectedOutputs as $index => $expectedOutput ) {
			$actualOutput = $actualOutputs[$index];
			// assertXmlStringEqualsXmlString ignores order of attributes
			$this->assertXmlStringEqualsXmlString(
				$expectedOutput['output'], $actualOutput['output'], 'Output differs'
			);
			if ( array_key_exists( 'core-validation', $expectedOutput ) ) {
				$this->assertArrayHasKey(
					'core-validation', $actualOutput, 'Core validation unexpectedly successful'
				);
				$this->assertArrayEquals(
					$expectedOutput['core-validation'], $actualOutput['core-validation'],
					'Core validation differs'
				);
			}
		}
	}

	public static function provideTestCases() {
		$file = file_get_contents( __DIR__ . "/data/reference.json" );
		$json = json_decode( $file, true );
		foreach ( $json as $hash => $entry ) {
			yield $hash . ': ' . substr( $entry['input'], 0, 20 ) => [ $hash, $entry ];
		}
	}

	private function getMathConfig() {
		return new MathConfig(
			new ServiceOptions( MathConfig::CONSTRUCTOR_OPTIONS, [
					'MathDisableTexFilter' => MathConfig::ALWAYS,
					'MathValidModes' => [ MathConfig::MODE_NATIVE_MML ],
					'MathEntitySelectorFallbackUrl' => '\\urs',
				] ),
			$this->createMock( ExtensionRegistry::class )

		);
	}

	protected function setUp(): void {
		parent::setUp();
		MediaWikiServices::allowGlobalInstanceAfterUnitTests();
		$this->mathConfig = $this->getMathConfig();
		$this->hookContainer = $this->createHookContainer();
		$this->mainConfig = new HashConfig();
		$this->mainConfig->set( 'MathEnableFormulaLinks', false );
	}
}
