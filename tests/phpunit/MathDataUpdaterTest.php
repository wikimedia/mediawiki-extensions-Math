<?php

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Entity\PropertyDataTypeMatcher;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;

/**
 * Test the database access and core functionality of MathRenderer.
 *
 * @covers MathDataUpdater
 **
 * @license GPL-2.0-or-later
 */
class MathDataUpdaterTest extends MediaWikiTestCase {

	/**
	 * @var PropertyId
	 */
	private $mathProperty;
	/**
	 * @var PropertyId
	 */
	private $otherProperty;

	/**
	 * @inheritDoc
	 */
	protected function setUp() {
		parent::setUp();
		$this->mathProperty = new PropertyId( 'P' . DummyPropertyDataTypeLookup::$mathId );
		$this->otherProperty = new PropertyId( 'P' . ( DummyPropertyDataTypeLookup::$mathId + 1 ) );
	}

	public function testNoMath() {
		$matcher = new PropertyDataTypeMatcher( new DummyPropertyDataTypeLookup() );
		$updater = new MathDataUpdater( $matcher );
		$statement =
			$this->getMockBuilder( Wikibase\DataModel\Statement\Statement::class )
				->setMethods( [ 'getPropertyId' ] )
				->disableOriginalConstructor()
				->getMock();
		$statement->method( 'getPropertyId' )->willReturn( $this->otherProperty );
		/** @var Wikibase\DataModel\Statement\Statement $statement */
		$updater->processStatement( $statement );
		$parserOutput = $this->getMockBuilder( ParserOutput::class )->setMethods( [
			'addModules',
			'addModuleStyles',
		] )->getMock();
		$parserOutput->expects( $this->never() )->method( 'addModules' );
		$parserOutput->expects( $this->never() )->method( 'addModuleStyles' );
		/** @var ParserOutput $parserOutput */
		$updater->updateParserOutput( $parserOutput );
	}

	public function testMath() {
		$matcher = new PropertyDataTypeMatcher( new DummyPropertyDataTypeLookup() );
		$updater = new MathDataUpdater( $matcher );
		$statement =
			$this->getMockBuilder( Wikibase\DataModel\Statement\Statement::class )
				->setMethods( [ 'getPropertyId' ] )
				->disableOriginalConstructor()
				->getMock();
		$statement->method( 'getPropertyId' )->willReturn( $this->mathProperty );
		/** @var Wikibase\DataModel\Statement\Statement $statement */
		$updater->processStatement( $statement );
		$parserOutput = $this->getMockBuilder( ParserOutput::class )->setMethods( [
			'addModules',
			'addModuleStyles',
		] )->getMock();
		$parserOutput->expects( $this->once() )->method( 'addModules' );
		$parserOutput->expects( $this->once() )->method( 'addModuleStyles' );
		/** @var ParserOutput $parserOutput */
		$updater->updateParserOutput( $parserOutput );
	}
}

class DummyPropertyDataTypeLookup implements PropertyDataTypeLookup {
	/**
	 * @var int
	 */
	public static $mathId = 1;

	/**
	 * Returns the data type for the Property of which the id is given.
	 *
	 * @since 2.0
	 *
	 * @param \Wikibase\DataModel\Entity\PropertyId $propertyId
	 *
	 * @return string
	 * @throws \Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException
	 */
	public function getDataTypeIdForProperty( \Wikibase\DataModel\Entity\PropertyId $propertyId ) {
		return $propertyId->getNumericId() == self::$mathId ? 'math' : 'not-math';
	}
}
