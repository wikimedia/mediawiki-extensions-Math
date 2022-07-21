<?php

namespace MediaWiki\Extension\Math\Tests;

use HashConfig;
use MediaWiki\Extension\Math\MathWikibaseConfig;
use MediaWikiUnitTestCase;
use Site;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * @covers \MediaWiki\Extension\Math\MathWikibaseConfig
 */
class MathWikibaseConfigTest extends MediaWikiUnitTestCase {

	private function getConfig() {
		$entityRevisionLookup = $this->createMock( EntityRevisionLookup::class );
		$labelDescriptionLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		return new MathWikibaseConfig(
			new BasicEntityIdParser(),
			$entityRevisionLookup,
			$labelDescriptionLookupFactory,
			new Site(),
			new HashConfig( [
				'MathWikibasePropertyIdHasPart' => 'P1',
				'MathWikibasePropertyIdDefiningFormula' => 'P2',
				'MathWikibasePropertyIdQuantitySymbol' => 'P3'
			] ) );
	}

	public function testGetEntityRevisionLookup() {
		$config = $this->getConfig();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityRevisionLookup', $config->getEntityRevisionLookup() );
	}

	public function testGetSite() {
		$config = $this->getConfig();
		$this->assertInstanceOf( 'Site', $config->getSite() );
	}

	public function testGetPropertyIdQuantitySymbol() {
		$config = $this->getConfig();
		$this->assertEquals( 'P3', $config->getPropertyIdQuantitySymbol()->getLocalPart() );
	}

	public function testGetLabelLookupFactory() {
		$config = $this->getConfig();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory',
			$config->getLabelLookupFactory() );
	}

	public function testGetIdParser() {
		$config = $this->getConfig();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\BasicEntityIdParser', $config->getIdParser() );
	}

	public function testHasSite() {
		$config = $this->getConfig();
		$this->assertTrue( $config->hasSite() );
	}

	public function testGetPropertyIdHasPart() {
		$config = $this->getConfig();
		$this->assertEquals( 'P1', $config->getPropertyIdHasPart()->getLocalPart() );
	}

	public function testGetPropertyIdDefiningFormula() {
		$config = $this->getConfig();
		$this->assertEquals( 'P2', $config->getPropertyIdDefiningFormula()->getLocalPart() );
	}
}
