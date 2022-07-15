<?php

namespace MediaWiki\Extension\Math\Tests;

use DataValues\StringValue;
use Language;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\MathWikibaseConnector;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWikiUnitTestCase;
use MWException;
use Psr\Log\LoggerInterface;
use Site;
use TestLogger;
use Wikibase\Client\RepoLinker;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\SiteLink;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \MediaWiki\Extension\Math\MathWikibaseConnector
 */
class MathWikibaseConnectorTest extends MediaWikiUnitTestCase {

	private const EXAMPLE_URL = 'https://example.com/';

	private const TEST_ITEMS = [
		'Q1' => [ 'mass–energy equivalence', 'physical law relating mass to energy', 'E = mc^2' ],
		'Q2' => [ 'energy', 'measure for the ability of a system to do work', 'E' ],
		'Q3' => [
			'speed of light',
			'speed at which all massless particles and associated fields travel in vacuum',
			'c'
		],
		'Q4' => [
			'mass',
			'property of matter to resist changes of the state of motion and to attract other bodies',
			'm'
		]
	];

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

	public function testFetchWithStorageIssue() {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$entityRevisionLookup->method( 'getEntityRevision' )
			->willThrowException( new StorageException( 'Invalid code' ) );
		$mathWikibase = $this->getWikibaseConnector( null, null, $entityRevisionLookup );

		$this->expectException( 'InvalidArgumentException' );
		$this->expectErrorMessage( 'Non-existing Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', '&' );
	}

	public function testFetchNonExistingId() {
		$mathWikibase = $this->getWikibaseConnector();
		$this->expectException( 'InvalidArgumentException' );
		$this->expectErrorMessage( 'Non-existing Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', 'en' );
	}

	public function testFailSafeFaultyPropertySetup() {
		$dummyItemId = new ItemId( 'Q1' );
		$parserMock = $this->createMock( BasicEntityIdParser::class );
		$parserMock->method( 'parse' )
			->willReturnCallback(
				static function ( string $id ) {
					if ( $id === 'Q1' ) {
						return new ItemId( 'Q1' );
					} else {
						throw new \ConfigException();
					}
				} );

		$revisionLookupMock = $this->createMock( EntityRevisionLookup::class );
		$revisionLookupMock->expects( $this->once() )
			->method( 'getEntityRevision' )
			->with( $dummyItemId )
			->willReturn( null );

		// non-existing properties should not result in errors on initialization
		$mathWikibase = $this->getWikibaseConnector(
			null,
			null,
			$revisionLookupMock,
			null,
			$parserMock
		);

		// but obviously on non-existing errors when trying to fetch information
		$this->expectException( 'InvalidArgumentException' );
		$this->expectErrorMessage( 'Non-existing Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( 'Q1', 'en' );
	}

	public function testFetchMalformedId() {
		$parserMock = $this->createMock( BasicEntityIdParser::class );
		$parserMock->method( 'parse' )
			->willReturnCallback(
				static function ( string $id ) {
					if ( $id === '1' ) {
						throw new EntityIdParsingException();
					} else {
						return null;
					}
				} );

		$mathWikibase = $this->getWikibaseConnector( null, null, null, null, $parserMock );
		$this->expectException( 'InvalidArgumentException' );
		$this->expectErrorMessage( 'Invalid Wikibase ID.' );
		$mathWikibase->fetchWikibaseFromId( '1', 'en' );
	}

	public function testFetchNonItem() {
		// a mocked Item does not pass instanceof, hence the InvalidArgumentException
		$entityRevisionMock = $this->createMock( EntityRevision::class );
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems( $entityRevisionMock );
		$this->expectException( 'InvalidArgumentException' );
		$this->expectErrorMessage( 'The specified Wikibase ID does not represented an item.' );
		$wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
	}

	public function testFetchEmptyItem() {
		$itemId = new ItemId( 'Q1' );
		$item = new Item( $itemId );
		$revision = new EntityRevision( $item );

		$parserMock = $this->createMock( BasicEntityIdParser::class );
		$parserMock->method( 'parse' )
			->willReturnCallback(
				static function ( string $id ) {
					if ( str_starts_with( $id, 'Q' ) ) {
						return new ItemId( $id );
					} else {
						throw new \ConfigException();
					}
				} );

		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems(
			$revision,
			false,
			null,
			$parserMock
		);
		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
		$this->assertEquals( $itemId, $wikibaseInfo->getId() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][0], $wikibaseInfo->getLabel() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][1], $wikibaseInfo->getDescription() );
		$this->assertCount( 0, $wikibaseInfo->getParts() );
		$this->assertFalse( $wikibaseInfo->hasParts() );
		$this->assertNull( $wikibaseInfo->getSymbol() );
	}

	public function testFetchItemWithFormula() {
		$itemId = new ItemId( 'Q1' );
		$item = new Item( $itemId );
		$revision = new EntityRevision( $item );

		$formulaValue = new StringValue( self::TEST_ITEMS[ 'Q1' ][2] );
		$definingFormulaStatement = new Statement( new PropertyValueSnak(
			new NumericPropertyId( 'P2' ),
			$formulaValue
		) );

		$item->setStatements( new StatementList( $definingFormulaStatement ) );

		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems( $revision );
		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
		$this->assertFalse( $wikibaseInfo->hasParts() );
		$this->assertEquals( $formulaValue, $wikibaseInfo->getSymbol() );
	}

	/**
	 * @dataProvider provideItemSetups
	 */
	public function testFetchMassEnergyEquivalenceHasPartsItem( Item $item ) {
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems( new EntityRevision( $item ) );
		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );

		$this->assertEquals( $item->getId(), $wikibaseInfo->getId() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][0], $wikibaseInfo->getLabel() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][1], $wikibaseInfo->getDescription() );
		$this->assertEquals( self::TEST_ITEMS[ 'Q1' ][2], $wikibaseInfo->getSymbol()->getValue() );

		$this->assertTrue( $wikibaseInfo->hasParts() );
		$parts = $wikibaseInfo->getParts();
		$this->assertCount( 3, $parts );
		foreach ( $parts as $part ) {
			$key = $part->getId()->getSerialization();
			$this->assertEquals( self::TEST_ITEMS[ $key ][0], $part->getLabel() );
			$this->assertEquals( self::TEST_ITEMS[ $key ][1], $part->getDescription() );
			$this->assertEquals( self::TEST_ITEMS[ $key ][2], $part->getSymbol()->getValue() );
			$this->assertEquals( self::EXAMPLE_URL, $part->getUrl() );
		}
	}

	/**
	 * @dataProvider provideItemSetups
	 */
	public function testFetchMassEnergyWithStorageExceptionLogging( Item $item ) {
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems( new EntityRevision( $item ), true );

		$this->expectError();
		$this->expectErrorMessage( 'LOG[warning]: Cannot fetch URL for EntityId Q3. Reason: Test Exception' );
		$wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
	}

	/**
	 * @dataProvider provideItemSetups
	 */
	public function testFetchMassEnergyWithStorageException( Item $item ) {
		$wikibaseConnector = $this->getWikibaseConnectorWithExistingItems(
			new EntityRevision( $item ),
			true,
			LoggerFactory::getInstance( 'Math' )
		);

		$wikibaseInfo = $wikibaseConnector->fetchWikibaseFromId( 'Q1', 'en' );
		$this->assertTrue( $wikibaseInfo->hasParts() );
		$parts = $wikibaseInfo->getParts();
		$this->assertCount( 3, $parts );
		foreach ( $parts as $part ) {
			$key = $part->getId()->getSerialization();
			if ( $key === 'Q3' ) {
				$this->assertNull( $part->getUrl() );
			} else {
				$this->assertEquals( self::EXAMPLE_URL, $part->getUrl() );
			}
		}
	}

	public function provideItemSetups(): array {
		return [
			[ $this->setupMassEnergyEquivalenceItem( true ) ],
			[ $this->setupMassEnergyEquivalenceItem( false ) ],
		];
	}

	private function setupMassEnergyEquivalenceItem(
		bool $hasPartMode
	) {
		$partPropertyId = new NumericPropertyId( $hasPartMode ? 'P1' : 'P4' );
		$symbolPropertyId = new NumericPropertyId( $hasPartMode ? 'P3' : 'P5' );
		$items = [];
		$statements = [];
		foreach ( self::TEST_ITEMS as $key => $itemInfo ) {
			$itemId = new ItemId( $key );
			$items[ $key ] = new Item( $itemId );

			$siteLinkMock = $this->createMock( SiteLink::class );
			$siteLinkMock->method( 'getSiteId' )->willReturn( '' );
			$siteLinkMock->method( 'getPageName' )->willReturn( '' );
			$items[ $key ]->addSiteLink( $siteLinkMock );

			if ( $key === 'Q1' ) {
				continue;
			}

			$partSnak = new PropertyValueSnak(
				$partPropertyId,
				$hasPartMode ? new EntityIdValue( $items[ $key ]->getId() ) : new StringValue( $itemInfo[2] )
			);
			$partQualifier = new PropertyValueSnak(
				$symbolPropertyId,
				$hasPartMode ? new StringValue( $itemInfo[2] ) : new EntityIdValue( $items[ $key ]->getId() )
			);

			$statement = new Statement( $partSnak );
			$statement->setQualifiers( new SnakList( [ $partQualifier ] ) );
			$statements[] = $statement;
		}

		$mainFormulaValue = new StringValue( self::TEST_ITEMS[ 'Q1' ][2] );
		$definingFormulaStatement = new Statement( new PropertyValueSnak(
			new NumericPropertyId( 'P2' ),
			$mainFormulaValue
		) );

		$statementList = new StatementList( ...$statements );
		$statementList->addStatement( $definingFormulaStatement );
		$items[ 'Q1' ]->setStatements( $statementList );
		return $items[ 'Q1' ];
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

	private function getWikibaseConnectorWithExistingItems(
		EntityRevision $entityRevision,
		bool $storageExceptionOnQ3 = false,
		LoggerInterface $logger = null,
		EntityIdParser $parser = null
	): MathWikibaseConnector {
		$revisionLookupMock = $this->createMock( EntityRevisionLookup::class );
		$revisionLookupMock->method( 'getEntityRevision' )->willReturnCallback(
			static function ( EntityId $entityId ) use ( $entityRevision, $storageExceptionOnQ3 ) {
				if ( $storageExceptionOnQ3 && $entityId->getSerialization() === 'Q3' ) {
					throw new StorageException( 'Test Exception' );
				} else {
					return $entityRevision;
				}
			}
		);
		$revisionLookupMock->expects( $this->atLeastOnce() )
			->method( 'getEntityRevision' );

		$fallbackLabelDescriptionLookupFactoryMock = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$languageMock = $this->createMock( Language::class );
		$languageFactoryMock = $this->createMock( LanguageFactory::class );
		$languageFactoryMock->method( 'getLanguage' )
			->with( 'en' )
			->willReturn( $languageMock );
		$fallbackLabelDescriptionLookupFactoryMock->method( 'newLabelDescriptionLookup' )
			->with( $languageMock )
			->willReturnCallback( [ $this, 'newLabelDescriptionLookup' ] );

		return $this->getWikibaseConnector(
			$languageFactoryMock,
			$fallbackLabelDescriptionLookupFactoryMock,
			$revisionLookupMock,
			$logger,
			$parser
		);
	}

	/**
	 * @param LanguageFactory|null $languageFactory
	 * @param FallbackLabelDescriptionLookupFactory|null $labelDescriptionLookupFactory
	 * @param EntityRevisionLookup|null $entityRevisionLookupMock
	 * @param LoggerInterface|null $logger
	 * @param EntityIdParser|null $parser
	 * @return MathWikibaseConnector
	 */
	public function getWikibaseConnector(
		LanguageFactory $languageFactory = null,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory = null,
		EntityRevisionLookup $entityRevisionLookupMock = null,
		LoggerInterface $logger = null,
		EntityIdParser $parser = null
	): MathWikibaseConnector {
		$labelDescriptionLookupFactory = $labelDescriptionLookupFactory ?:
			$this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$entityRevisionLookup = $entityRevisionLookupMock ?:
			$this->createMock( EntityRevisionLookup::class );
		$languageFactory = $languageFactory ?: $this->createMock( LanguageFactory::class );
		$site = $this->createMock( Site::class );
		$site->method( 'getGlobalId' )->willReturn( '' );
		$site->method( 'getPageUrl' )->willReturn( self::EXAMPLE_URL );
		return new MathWikibaseConnector(
			new ServiceOptions( MathWikibaseConnector::CONSTRUCTOR_OPTIONS, [
				'MathWikibasePropertyIdHasPart' => 'P1',
				'MathWikibasePropertyIdDefiningFormula' => 'P2',
				'MathWikibasePropertyIdQuantitySymbol' => 'P3',
				'MathWikibasePropertyIdInDefiningFormula' => 'P4',
				'MathWikibasePropertyIdSymbolRepresents' => 'P5'
			] ),
			$this->newConnector(),
			$languageFactory,
			$entityRevisionLookup,
			$labelDescriptionLookupFactory,
			$site,
			$parser ?: new BasicEntityIdParser(),
			$logger ?: new TestLogger()
		);
	}

	public function newLabelDescriptionLookup(): FallbackLabelDescriptionLookup {
		$lookup = $this->createMock( FallbackLabelDescriptionLookup::class );

		$lookup->method( 'getLabel' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				if ( self::TEST_ITEMS[ $entityId->getSerialization() ] !== null ) {
					return new Term( 'en', self::TEST_ITEMS[ $entityId->getSerialization() ][0] );
				} else {
					return null;
				}
			} );

		$lookup->method( 'getDescription' )
			->willReturnCallback( static function ( EntityId $entityId ) {
				if ( self::TEST_ITEMS[ $entityId->getSerialization() ] !== null ) {
					return new Term( 'en', self::TEST_ITEMS[ $entityId->getSerialization() ][1] );
				} else {
					return null;
				}
			} );

		return $lookup;
	}
}
