<?php

namespace MediaWiki\Extension\Math\Tests\Render;

use InvalidArgumentException;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathNativeMML;
use MediaWiki\Extension\Math\MathSource;
use MediaWiki\Extension\Math\Render\RendererFactory;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Wikimedia\ObjectCache\HashBagOStuff;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @covers \MediaWiki\Extension\Math\Render\RendererFactory
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class RendererFactoryTest extends MediaWikiIntegrationTestCase {

	private MockObject|MathConfig $mathConfig;

	private UserOptionsLookup|MockObject $userOptionsLookup;

	private LoggerInterface|MockObject $logger;

	private WANObjectCache|MockObject $cache;

	private RendererFactory $factory;
	private const FAKE_INPUT_HASH = 'global:MediaWiki\Extension\Math\MathRenderer:test_hash_123';
	private const FAKE_DATA = [
		'math_mode' => MathConfig::MODE_SOURCE,
		'tex' => 'x^2'
	];

	protected function setUp(): void {
		parent::setUp();

		$this->mathConfig = $this->createMock( MathConfig::class );
		$this->userOptionsLookup = $this->createMock( UserOptionsLookup::class );
		$this->logger = $this->createMock( LoggerInterface::class );
		$fakeWAN = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );

		$fakeWAN->set( self::FAKE_INPUT_HASH, self::FAKE_DATA );
		$this->cache = $fakeWAN;

		$this->mathConfig->method( 'getValidRenderingModes' )->willReturn( [
			MathConfig::MODE_SOURCE,
			MathConfig::MODE_NATIVE_MML
		] );

		$this->userOptionsLookup->method( 'getDefaultOption' )
			->with( 'math' )
			->willReturn( MathConfig::MODE_NATIVE_MML );

		$this->factory = new RendererFactory(
			$this->mathConfig,
			$this->userOptionsLookup,
			$this->logger,
			$this->cache
		);
	}

	public function testGetRendererForSourceMode() {
		$tex = 'x^2 + y^2 = z^2';

		$renderer = $this->factory->getRenderer( $tex, [], MathConfig::MODE_SOURCE );

		$this->assertInstanceOf( MathSource::class, $renderer );
		$this->assertEquals( $tex, $renderer->getTex() );
	}

	public function testGetRendererForNativeMMLMode() {
		$tex = 'x^2 + y^2 = z^2';

		$renderer = $this->factory->getRenderer( $tex, [], MathConfig::MODE_NATIVE_MML );

		$this->assertInstanceOf( MathNativeMML::class, $renderer );
	}

	public function testGetRendererWithInvalidMode() {
		$tex = 'x^2 + y^2 = z^2';
		$invalidMode = 'invalid_mode';

		$renderer = $this->factory->getRenderer( $tex, [], $invalidMode );

		// Fall back from invalid to default (MODE_NATIVE_MML)
		$this->assertInstanceOf( MathNativeMML::class, $renderer );
	}

	public function testGetRendererWithForceMathMode() {
		$tex = 'x^2 + y^2 = z^2';
		$params = [ 'forcemathmode' => MathConfig::MODE_SOURCE ];

		$renderer = $this->factory->getRenderer( $tex, $params, MathConfig::MODE_NATIVE_MML );

		// Favor forced mode over site/user default
		$this->assertInstanceOf( MathSource::class, $renderer );
	}

	public function testGetRendererWithChemParam() {
		$tex = 'H_2O';
		$params = [ 'chem' => true ];

		$renderer = $this->factory->getRenderer( $tex, $params, MathConfig::MODE_SOURCE );

		// Chemistry should ignore MODE_SOURCE and fall back to default (MODE_NATIVE_MML)
		$this->assertInstanceOf( MathNativeMML::class, $renderer );
		$this->assertEquals( 'chem', $renderer->getInputType() );
	}

	public function testGetFromHash() {
		// Create a partial mock to avoid actually initializing from cache
		$factory = $this->getMockBuilder( RendererFactory::class )
			->setConstructorArgs( [
				$this->mathConfig,
				$this->userOptionsLookup,
				$this->logger,
				$this->cache
			] )
			->onlyMethods( [ 'getRenderer' ] )
			->getMock();

		$mockRenderer = $this->createMock( MathSource::class );
		$mockRenderer->expects( $this->once() )
			->method( 'initializeFromCache' )
			->with( self::FAKE_DATA );

		$factory->expects( $this->once() )
			->method( 'getRenderer' )
			->with( '', [], MathConfig::MODE_SOURCE )
			->willReturn( $mockRenderer );

		$result = $factory->getFromHash( 'test_hash_123' );
		$this->assertSame( $mockRenderer, $result );
	}

	public function testGetFromHashWithInvalidCache() {
		$inputHash = 'invalid_hash';

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Cache key is invalid' );

		$this->factory->getFromHash( $inputHash );
	}
}
