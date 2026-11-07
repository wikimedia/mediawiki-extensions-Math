<?php

namespace MediaWiki\Extension\Math\Render;

use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWikiUnitTestCase;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectCache\WANObjectCache;

class RendererFactoryTest extends MediawikiUnitTestCase {

	private function createRendererFactory( array $parameters ): RendererFactory {
		$mathConfig = $this->createMock( MathConfig::class );
		$mathConfig
			->expects( self::any() )
			->method( 'getValidRenderingModes' )
			->willReturn( $parameters['validModes'] ?? MathConfig::SUPPORTED_MODES );

		$userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$userOptionsLookup
			->expects( self::any() )
			->method( 'getDefaultOption' )
			->with( 'math' )
			->willReturn( $parameters['defaultMode'] ?? MathConfig::MODE_NATIVE_MML );

		$logger = $this->createStub( LoggerInterface::class );

		$cache = $this->createStub( WANObjectCache::class );

		return new RendererFactory(
			$mathConfig,
			$userOptionsLookup,
			$logger,
			$cache
		);
	}

	public static function determineModeDataProvider(): array {
		return [
			[
				[], [], MathConfig::MODE_SOURCE, MathConfig::MODE_SOURCE
			],
			[
				[], [], MathConfig::MODE_MATHML, MathConfig::MODE_NATIVE_MML
			],
			[
				[], [], MathConfig::MODE_NATIVE_MML, MathConfig::MODE_NATIVE_MML
			],
			[
				[],
				[ 'forcemathmode' => MathConfig::MODE_MATHML ],
				MathConfig::MODE_NATIVE_MML,
				MathConfig::MODE_NATIVE_MML
			],
			[
				[],
				[ 'forcemathmode' => MathConfig::MODE_NATIVE_MML ],
				MathConfig::MODE_MATHML,
				MathConfig::MODE_NATIVE_MML
			],
			[
				[ 'validModes' =>
					[
						MathConfig::MODE_SOURCE,
						MathConfig::MODE_NATIVE_MML
					]
				],
				[],
				MathConfig::MODE_NATIVE_MML,
				MathConfig::MODE_NATIVE_MML
			],
			[
				[ 'validModes' =>
					[
						MathConfig::MODE_SOURCE,
						MathConfig::MODE_NATIVE_MML
					]
				],
				[],
				MathConfig::MODE_MATHML,
				MathConfig::MODE_NATIVE_MML
			],
			[
				[ 'validModes' =>
					[
						MathConfig::MODE_SOURCE,
						MathConfig::MODE_NATIVE_MML
					]
				],
				[ 'forcemathmode' => MathConfig::MODE_MATHML ],
				MathConfig::MODE_SOURCE,
				MathConfig::MODE_NATIVE_MML
			],
			[
				[ 'validModes' =>
					[
						MathConfig::MODE_SOURCE,
						MathConfig::MODE_MATHML
					],
					'defaultMode' => MathConfig::MODE_MATHML
				],
				[ 'forcemathmode' => MathConfig::MODE_NATIVE_MML ],
				MathConfig::MODE_SOURCE,
				MathConfig::MODE_MATHML
			],
			[
				[],
				[
					'chem' => true,
				],
				MathConfig::MODE_NATIVE_MML,
				MathConfig::MODE_NATIVE_MML,
				[
					'chem' => true,
					'type' => 'chem'
				]
			],
			[
				[],
				[
					'chem' => true,
				],
				MathConfig::MODE_SOURCE,
				MathConfig::MODE_NATIVE_MML,
				[
					'chem' => true,
					'type' => 'chem'
				]
			],
			[
				[],
				[
					'chem' => true,
					'forcemathmode' => MathConfig::MODE_NATIVE_MML
				],
				MathConfig::MODE_SOURCE,
				MathConfig::MODE_NATIVE_MML,
				[
					'chem' => true,
					'forcemathmode' => MathConfig::MODE_NATIVE_MML,
					'type' => 'chem'
				]
			],
			[
				[],
				[
					'chem' => true,
					'forcemathmode' => MathConfig::MODE_SOURCE
				],
				MathConfig::MODE_NATIVE_MML,
				MathConfig::MODE_NATIVE_MML,
				[
					'chem' => true,
					'forcemathmode' => MathConfig::MODE_SOURCE,
					'type' => 'chem'
				]
			],
			[
				[],
				[
					'type' => 'broken_type',
				],
				MathConfig::MODE_NATIVE_MML,
				MathConfig::MODE_NATIVE_MML,
				[
					'type' => 'broken_type',
				],
			],
			[
				[],
				[
					'type' => 'pmml',
				],
				MathConfig::MODE_NATIVE_MML,
				MathConfig::MODE_NATIVE_MML
			],
		];
	}

	/**
	 * @param array $factoryParameters
	 * @param array $tagParameters
	 * @param string $givenMode
	 * @param string $expectedMode
	 * @param array|null $expectedArgs
	 *
	 * @return void
	 * @dataProvider determineModeDataProvider
	 * @covers \MediaWiki\Extension\Math\Render\RendererFactory::determineMode
	 */
	public function testDetermineMode(
		array $factoryParameters,
		array $tagParameters,
		string $givenMode,
		string $expectedMode,
		?array $expectedArgs = null
	): void {
		$factory = $this->createRendererFactory( $factoryParameters );
		[ $mode, $receivedParams ] = $factory->determineMode( $givenMode, $tagParameters );
		$this->assertEquals(
			$expectedMode,
			$mode,
			'Determine mode returns unexpected mode'
		);
		$this->assertEquals(
			$expectedArgs ?? $tagParameters,
			$receivedParams,
			'Determine mode modifies parameters in unexpected behavior'
		);
	}
}
