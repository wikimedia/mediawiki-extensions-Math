<?php

use DataValues\StringValue;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;

/**
 * A class that connects with the local instance of wikibase to fetch
 * information from single items. There is always only one instance of this class.
 *
 * @see WikibaseRepo::getDefaultInstance()      the instance thats been used to fetch the data
 * @see MathWikidataConnector::getInstance()    to get an instance of the class
 */
class MathWikidataConnector {
	/**
	 * @var MathWikidataConnector
	 */
	private static $instance;

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
	 * MathWikidataConnector constructor.
	 */
	private function __construct() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->idParser = $wikibaseRepo->getEntityIdParser();
		$this->entityRevisionLookup = $wikibaseRepo->getEntityRevisionLookup();
		$this->labelLookupFactory = $wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory();
		$this->initializerPropertyIds();
	}

	/**
	 * Initializes the PropertyId elements
	 */
	private function initializerPropertyIds() {
		global
			$wgWikidataPropertyIdHasPart,
			$wgWikidataPropertyIdDefiningFormula,
			$wgWikidataPropertyIdQuantitySymbol;

		$this->propertyIdHasPart = $this->idParser->parse( $wgWikidataPropertyIdHasPart );
		$this->propertyIdQuantitySymbol = $this->idParser->parse( $wgWikidataPropertyIdQuantitySymbol );
		$this->propertyIdDefiningFormula = $this->idParser->parse( $wgWikidataPropertyIdDefiningFormula );
	}

	/**
	 * @param string $qid
	 * @param string $langCode the language to fetch data
	 *          (may fallback if requested language does not exist)
	 *
	 * @return MathWikidataInfo the object may be empty if no information can be fetched.
	 * @throws InvalidArgumentException if the language code does not exist or the given
	 * id does not exist
	 */
	public function fetchWikidataFromId( $qid, $langCode ) {
		try {
			$lang = Language::factory( $langCode );
		} catch ( MWException $e ) {
			throw new InvalidArgumentException( "Invalid language code specified." );
		}

		$langLookup = $this->labelLookupFactory->newLabelDescriptionLookup( $lang );

		try {
			$entityId = $this->idParser->parse( $qid ); // exception if the given ID is invalid
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidArgumentException( "Invalid Wikidata ID." );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			throw new InvalidArgumentException( "Non-existing Wikidata ID." );
		} catch ( StorageException $e ) {
			throw new InvalidArgumentException( "Non-existing Wikidata ID." );
		}

		if ( !$entityId || !$entityRevision ) {
			throw new InvalidArgumentException( "Non-existing Wikidata ID." );
		}

		$entity = $entityRevision->getEntity();
		$output = new MathWikidataInfo( $entityId );

		if ( $entity instanceof Item ) {
			$this->fetchLabelDescription( $output, $langLookup );
			$this->fetchStatements( $output, $entity, $langLookup );
			return $output;
		} else { // we only allow Wikidata items
			throw new InvalidArgumentException( "The specified Wikidata ID does not represented an item." );
		}
	}

	/**
	 * Fetches only label and description from an entity.
	 * @param MathWikidataInfo $output the entity id of the entity
	 * @param LabelDescriptionLookup $langLookup a lookup handler to fetch right languages
	 * @return MathWikidataInfo filled up with label and description
	 */
	private function fetchLabelDescription(
		MathWikidataInfo $output,
		LabelDescriptionLookup $langLookup ) {
		$label = $langLookup->getLabel( $output->getId() );
		$desc = $langLookup->getDescription( $output->getId() );

		if ( $label ) {
			$output->setLabel( $label->getText() );
		}

		if ( $desc ) {
			$output->setDescription( $desc->getText() );
		}

		return $output;
	}

	/**
	 * Fetches 'has part' statements from a given item element with a defined lookup object for
	 * the languages.
	 * @param MathWikidataInfo $output the output element
	 * @param Item $item item to fetch statements from
	 * @param LabelDescriptionLookup $langLookup
	 * @return MathWikidataInfo the updated $output object
	 */
	private function fetchStatements(
		MathWikidataInfo $output,
		Item $item,
		LabelDescriptionLookup $langLookup ) {
		$statements = $item->getStatements();

		$hasPartStatements = $statements->getByPropertyId( $this->propertyIdHasPart );
		$this->fetchHasPartSnaks( $output, $hasPartStatements, $langLookup );

		$symbolStatement = $statements->getByPropertyId( $this->propertyIdDefiningFormula );
		if ( $symbolStatement->count() < 1 ) { // if it's not a formula, it might be a symbol
			$symbolStatement = $statements->getByPropertyId( $this->propertyIdQuantitySymbol );
		}
		$this->fetchSymbol( $output, $symbolStatement );
		return $output;
	}

	/**
	 * Fetches the symbol or defining formula from a statement list and adds the symbol to the
	 * given info object
	 * @param MathWikidataInfo $output
	 * @param StatementList $statements
	 * @return MathWikidataInfo updated object
	 */
	private function fetchSymbol( MathWikidataInfo $output, StatementList $statements ) {
		foreach ( $statements as $statement ) {
			$snak = $statement->getMainSnak();
			if ( $snak instanceof PropertyValueSnak && $this->isQualifierDefinien( $snak ) ) {
				$dataVal = $snak->getDataValue();
				$symbol = new StringValue( $dataVal->getValue() );
				$output->setSymbol( $symbol );
				return $output;
			}
		}

		return $output;
	}

	/**
	 * Fetches single snaks from 'has part' statements
	 *
	 * @todo refactor this method once Wikibase has a more convenient way to handle snaks
	 *
	 * @param StatementList $statements the 'has part' statements
	 * @param LabelDescriptionLookup $langLookup
	 * @return MathWikidataInfo
	 */
	private function fetchHasPartSnaks(
		MathWikidataInfo $output,
		StatementList $statements,
		LabelDescriptionLookup $langLookup ) {
		foreach ( $statements as $statement ) {
			$snaks = $statement->getAllSnaks();
			$innerInfo = null;
			$symbol = null;

			foreach ( $snaks as $snak ) {
				if ( $snak instanceof PropertyValueSnak ) {
					if ( $this->isQualifierDefinien( $snak ) ) {
						$dataVal = $snak->getDataValue();
						$symbol = new StringValue( $dataVal->getValue() );
					} elseif ( $snak->getPropertyId()->equals( $this->propertyIdHasPart ) ) {
						$dataVal = $snak->getDataValue();
						$entityIdValue = $dataVal->getValue();
						if ( $entityIdValue instanceof EntityIdValue ) {
							$innerEntityId = $entityIdValue->getEntityId();
							$innerInfo = new MathWikidataInfo( $innerEntityId );
							$this->fetchLabelDescription( $innerInfo, $langLookup );
						}
					}
				}
			}

			if ( $innerInfo ) {
				$innerInfo->setSymbol( $symbol );
				$output->addHasPartElement( $innerInfo );
			}
		}

		return $output;
	}

	/**
	 * @param Snak $snak
	 * @return bool true if the given snak is either a defining formula or a quantity symbol
	 */
	private function isQualifierDefinien( Snak $snak ) {
		return $snak->getPropertyId()->equals( $this->propertyIdQuantitySymbol ) ||
			$snak->getPropertyId()->equals( $this->propertyIdDefiningFormula );
	}

	/**
	 * @todo should be refactored once there is an easier way to get the URL
	 * @param string $qID
	 * @return string
	 */
	public static function buildURL( $qID ) {
		$baseurl = WikibaseClient::getDefaultInstance()
			->getSettings()->getSetting( 'repoUrl' );
		$articlePath = WikibaseClient::getDefaultInstance()
			->getSettings()->getSetting( 'repoArticlePath' );
		$namespaces = WikibaseClient::getDefaultInstance()
			->getSettings()->getSetting( 'repoNamespaces' );

		$url = $baseurl . $articlePath;

		if ( $namespaces && $namespaces["item"] ) {
			$articleId = $namespaces["item"] . ":" . $qID;
		} else {
			$articleId = $qID;
		}

		// repoArticlePath contains the placeholder $1 for the page title
		// see: https://www.mediawiki.org/wiki/Manual:$wgArticlePath
		return str_replace( '$1', $articleId, $url );
	}

	/**
	 * Get the instance of the local Wikidata connector
	 * @return MathWikidataConnector
	 */
	public static function getInstance() : MathWikidataConnector {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
