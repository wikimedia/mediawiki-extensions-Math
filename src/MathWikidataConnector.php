<?php

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;

/**
 * A static class that connects with the local instance of wikibase to fetch
 * information from single items.
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
	 * @var MathFormatter
	 */
	private $mathFormatter;

	/**
	 * MathWikidataConnector constructor.
	 */
	private function __construct() {
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();
		$this->idParser = $wikibaseRepo->getEntityIdParser();
		$this->entityRevisionLookup = $wikibaseRepo->getEntityRevisionLookup();
		$this->labelLookupFactory = $wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory();
		$this->mathFormatter = new MathFormatter( SnakFormatter::FORMAT_HTML );
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
	 * @param string $id the qid of the item
	 * @param string $langCode the language to fetch data
	 *          (may fallback if requested language does not exist)
	 *
	 * @return MathWikidataInfo may be empty if no information can be fetched.
	 * In case of a successfull fetch, the result contains the following keys.
	 */
	public function fetchWikidataFromId( $id, $langCode ) {
		try {
			$lang = Language::factory( $langCode );
		} catch ( MWException $e ) {
			throw new InvalidArgumentException( "Invalid language code specified." );
		}

		$langLookup = $this->labelLookupFactory->newLabelDescriptionLookup( $lang );

		try {
			$entityId = $this->idParser->parse( $id ); // exception if the given ID is invalid
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );
		} catch ( EntityIdParsingException $e ) {
			throw new InvalidArgumentException( "Invalid Wikidata ID." );
		} catch ( RevisionedUnresolvedRedirectException $e ) {
			throw new InvalidArgumentException( "Non-existing Wikidata ID." );
		} catch ( StorageException $e ) {
			throw new InvalidArgumentException( "Non-existing Wikidata ID." );
		}

		$entity = $entityRevision->getEntity();
		$output = new MathWikidataInfo( $entityId );

		if ( $entity instanceof Item ) {
			$this->fetchLabelDescription( $output, $langLookup );
			$this->fetchStatements( $output, $entity, $langLookup );
			return $output;
		} else { // we only allow wikidata items
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
		$output->setLabel( $label->getText() );
		$output->setDescription( $desc->getText() );
		return $output;
	}

	/**
	 * Fetches 'has part' statements from a given item element with a defined lookup object for
	 * the right languages.
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
		$statements = $statements->getByPropertyId( $this->propertyIdHasPart );
		return $this->fetchSnaks( $output, $statements, $langLookup );
	}

	/**
	 * Fetches single snaks from 'has part' statements
	 * @param MathWikidataInfo $output the output element
	 * @param StatementList $statements the 'has part' statements
	 * @param LabelDescriptionLookup $langLookup
	 * @return MathWikidataInfo
	 */
	private function fetchSnaks(
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
						$stringValue = new StringValue( $dataVal->getValue() );
						$symbol = $this->mathFormatter->format( $stringValue );
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
	 * @param string $qID
	 * @return string
	 */
	public static function buildURL( $qID ) {
		$baseurl = Wikibase\Client\WikibaseClient::getDefaultInstance()
			->getSettings()->getSetting( 'repoUrl' );
		$articlePath = Wikibase\Client\WikibaseClient::getDefaultInstance()
			->getSettings()->getSetting( 'repoArticlePath' );
		$namespaces = Wikibase\Client\WikibaseClient::getDefaultInstance()
			->getSettings()->getSetting( 'repoNamespaces' );

		$url = $baseurl . $articlePath;

		if ( $namespaces && $namespaces["item"] ) {
			$articleId = $namespaces["item"] . ":" . $qID;
		} else {
			$articleId = $qID;
		}

		// repoArticlePath contains the placeholder $1 for the page title
		// see: https://www.mediawiki.org/wiki/Manual:$wgArticlePath
		return str_replace( "$1", $articleId, $url );
	}

	/**
	 * Get the instance of the local wikidata connector
	 * @return MathWikidataConnector
	 */
	public static function getInstance() : MathWikidataConnector {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
