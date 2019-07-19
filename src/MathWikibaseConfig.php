<?php

use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * A config class for the MathWikibaseConnector to connect with Wikibase
 * @see MathWikibaseConnector
 */
class MathWikibaseConfig {
	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @var PropertyId
	 */
	private $propertyIdHasPart;

	/**
	 * @var PropertyId
	 */
	private $propertyIdQuantitySymbol;

	/**
	 * @var PropertyId
	 */
	private $propertyIdDefiningFormula;

	/**
	 * MathWikibaseConfig constructor.
	 * @param EntityIdParser $entityIdParser
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	) {
		$this->idParser = $entityIdParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->labelLookupFactory = $labelDescriptionLookupFactory;

		$config = MediaWikiServices::getInstance()->getMainConfig();
		$this->propertyIdHasPart = $this->idParser->parse(
			$config->get( "WikibasePropertyIdHasPart" )
		);
		$this->propertyIdDefiningFormula = $this->idParser->parse(
			$config->get( "WikibasePropertyIdDefiningFormula" )
		);
		$this->propertyIdQuantitySymbol = $this->idParser->parse(
			$config->get( "WikibasePropertyIdQuantitySymbol" )
		);
	}

	/**
	 * @return EntityIdParser
	 */
	public function getIdParser() : EntityIdParser {
		return $this->idParser;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup() : EntityRevisionLookup {
		return $this->entityRevisionLookup;
	}

	/**
	 * @return LanguageFallbackLabelDescriptionLookupFactory
	 */
	public function getLabelLookupFactory() : LanguageFallbackLabelDescriptionLookupFactory {
		return $this->labelLookupFactory;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyIdHasPart() : PropertyId {
		return $this->propertyIdHasPart;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyIdQuantitySymbol() : PropertyId {
		return $this->propertyIdQuantitySymbol;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyIdDefiningFormula() : PropertyId {
		return $this->propertyIdDefiningFormula;
	}

	/**
	 * @return MathWikibaseConfig default config
	 */
	public static function getDefaultMathWikibaseConfig() : MathWikibaseConfig {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		return new MathWikibaseConfig(
			$wikibaseRepo->getEntityIdParser(),
			$wikibaseRepo->getEntityRevisionLookup(),
			$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory()
		);
	}
}
