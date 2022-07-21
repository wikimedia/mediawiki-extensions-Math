<?php

namespace MediaWiki\Extension\Math;

use Config;
use Site;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

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
	 * @var FallbackLabelDescriptionLookupFactory
	 */
	private $labelLookupFactory;

	/**
	 * @var Site
	 */
	private $site;

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
	 * @param EntityIdParser $entityIdParser
	 * @param EntityRevisionLookup $entityRevisionLookup
	 * @param FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	 * @param Site $site
	 * @param Config $config
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityRevisionLookup $entityRevisionLookup,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		Site $site,
		Config $config
	) {
		$this->idParser = $entityIdParser;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->labelLookupFactory = $labelDescriptionLookupFactory;
		$this->site = $site;

		$this->propertyIdHasPart = $this->idParser->parse(
			$config->get( "MathWikibasePropertyIdHasPart" )
		);
		$this->propertyIdDefiningFormula = $this->idParser->parse(
			$config->get( "MathWikibasePropertyIdDefiningFormula" )
		);
		$this->propertyIdQuantitySymbol = $this->idParser->parse(
			$config->get( "MathWikibasePropertyIdQuantitySymbol" )
		);
	}

	/**
	 * @return EntityIdParser
	 */
	public function getIdParser(): EntityIdParser {
		return $this->idParser;
	}

	/**
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup(): EntityRevisionLookup {
		return $this->entityRevisionLookup;
	}

	/**
	 * @return FallbackLabelDescriptionLookupFactory
	 */
	public function getLabelLookupFactory(): FallbackLabelDescriptionLookupFactory {
		return $this->labelLookupFactory;
	}

	/**
	 * @return Site
	 */
	public function getSite(): Site {
		return $this->site;
	}

	/**
	 * @deprecated Not needed, cannot return anything but true
	 *
	 * @return true
	 */
	public function hasSite() {
		return true;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyIdHasPart(): PropertyId {
		return $this->propertyIdHasPart;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyIdQuantitySymbol(): PropertyId {
		return $this->propertyIdQuantitySymbol;
	}

	/**
	 * @return PropertyId
	 */
	public function getPropertyIdDefiningFormula(): PropertyId {
		return $this->propertyIdDefiningFormula;
	}
}
