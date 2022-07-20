<?php

namespace MediaWiki\Extension\Math\Tests;

use MediaWiki\Extension\Math\MathWikibaseConfig;
use MediaWiki\Extension\Math\MathWikibaseConnector;
use MediaWikiIntegrationTestCase;
use TestLogger;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \MediaWiki\Extension\Math\MathWikibaseConnector
 */
class MathWikibaseConnectorTest extends MediaWikiIntegrationTestCase {

	private const EXAMPLE_URL = 'https://example.com/';

	public function testGetUrl() {
		$mathWikibase = $this->getWikibaseConnector();
		$this->assertEquals( self::EXAMPLE_URL . 'wiki/Special:EntityPage/Q42',
			$mathWikibase->buildURL( 'Q42' ) );
	}

	public function testFetchInvalidLanguage() {
		$mathWikibase = $this->getWikibaseConnector();
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
	 * @return MathWikibaseConnector
	 */
	public function getWikibaseConnector(): MathWikibaseConnector {
		return new MathWikibaseConnector( new MathWikibaseConfig( WikibaseClient::getEntityIdParser(),
				WikibaseClient::getStore()->getEntityRevisionLookup(),
				WikibaseClient::getFallbackLabelDescriptionLookupFactory(),
				WikibaseClient::getSite() ), $this->newConnector(), new TestLogger() );
	}

}
