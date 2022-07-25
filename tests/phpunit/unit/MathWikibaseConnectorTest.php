<?php

namespace MediaWiki\Extension\Math\Tests;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\MathWikibaseConnector;
use MediaWiki\Languages\LanguageFactory;
use MediaWikiUnitTestCase;
use MWException;
use Site;
use TestLogger;
use Wikibase\Client\RepoLinker;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \MediaWiki\Extension\Math\MathWikibaseConnector
 */
class MathWikibaseConnectorTest extends MediaWikiUnitTestCase {

	private const EXAMPLE_URL = 'https://example.com/';

	public function testGetUrl() {
		$mathWikibase = $this->getWikibaseConnector();
		$this->assertEquals( self::EXAMPLE_URL . 'wiki/Special:EntityPage/Q42',
			$mathWikibase->buildURL( 'Q42' ) );
	}

	public function testFetchInvalidLanguage() {
		$languageFactory = $this->createMock( LanguageFactory::class );
		$languageFactory->method( 'getLanguage' )
			->willThrowException( new MWException( 'Invalid code' ) );
		$mathWikibase = $this->getWikibaseConnector( $languageFactory );

		$this->expectException( 'InvalidArgumentException' );
		$this->expectErrorMessage( 'Invalid language code specified.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', '&' );
	}

	public function testFetchNonExistingId() {
		$mathWikibase = $this->getWikibaseConnector();
		$this->expectException( 'InvalidArgumentException' );
		$this->expectErrorMessage( 'Non-existing Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', 'en' );
	}

	private function newConnector(): RepoLinker {
		return new RepoLinker(
			new EntitySourceDefinitions(
				[
					new DatabaseEntitySource(
						'test',
						'testdb',
						[ 'item' => [ 'namespaceId' => 123, 'slot' => 'main' ] ],
						self::EXAMPLE_URL . 'entity',
						'',
						'',
						''
					)
				],
				new SubEntityTypesMapper( [] )
			),
			self::EXAMPLE_URL,
			'/wiki/$1',
	'' );
	}

	/**
	 * @param LanguageFactory|null $languageFactory
	 * @return MathWikibaseConnector
	 */
	public function getWikibaseConnector( LanguageFactory $languageFactory = null ): MathWikibaseConnector {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$labelDescriptionLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$languageFactory = $languageFactory ?: $this->createMock( LanguageFactory::class );
		return new MathWikibaseConnector(
			new ServiceOptions( MathWikibaseConnector::CONSTRUCTOR_OPTIONS, [
				'MathWikibasePropertyIdHasPart' => 'P1',
				'MathWikibasePropertyIdDefiningFormula' => 'P2',
				'MathWikibasePropertyIdQuantitySymbol' => 'P3'
			] ),
			$this->newConnector(),
			$languageFactory,
			$entityRevisionLookup,
			$labelDescriptionLookupFactory,
			new Site(),
			new BasicEntityIdParser(),
			new TestLogger() );
	}

}
