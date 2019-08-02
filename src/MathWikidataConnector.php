<?php

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
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
	 * @var string key for labels
	 */
	const KEY_LABEL = "label";

	/**
	 * @var string key for descriptions
	 */
	const KEY_DESC = "description";

	/**
	 * @var string key for symbols
	 */
	const KEY_SYMBOL = "symbol";

	/**
	 * @var string key for has part statements
	 */
	const KEY_HASPART = "has part";

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
	 * @return string[] may be empty if no information can be fetche.
	 * In case of a successfull fetch, the result contains the following keys:
	 * [
	 *      label        => the label of the entity
	 *      descritption => the description of the entity
	 *      has part     => [
	 *          [
	 *              label       => label of a part
	 *              description => description of a part
	 *              symbol      => the quantifier or defining formula as a string
	 *          ]
	 *      ]
	 * ]
	 *
	 * @throws MWException if the given language code was malformed
	 */
	public function fetchWikidataFromId( $id, $langCode ) {
		if ( !$this->isValidQID( $id ) ) {
			return [ "error" => "non-valid qid" ];
		}

		$lang = Language::factory( $langCode );
		$langLookup = $this->labelLookupFactory->newLabelDescriptionLookup( $lang );

		$entityId = $this->idParser->parse( $id );
		$entityRevision = $this->entityRevisionLookup->getEntityRevision( $entityId );

		if ( !$entityRevision ) {
			return [ "error" => "non existing revision id" ];
		}

		$entity = $entityRevision->getEntity();

		if ( $entity instanceof Item ) {
			$result = $this->fetchLabelDescription( $entityId, $langLookup );
			$result[self::KEY_HASPART] = $this->fetchStatements( $entity, $langLookup );
			return $result;
		} else { // we only allow wikidata items
			return [ "error" => "entity is not an item" ];
		}
	}

	/**
	 * Fetches only label and description from an entity.
	 * @param EntityId $entityId the entity id of the entity
	 * @param LabelDescriptionLookup $langLookup a lookup handler to fetch right languages
	 * @return iterable associated array with keys
	 *      MathWikidataConnector::KEY_LABEL
	 *      MathWikidataConnector::KEY_DESC
	 */
	private function fetchLabelDescription( EntityId $entityId, LabelDescriptionLookup $langLookup ) {
		$label = $langLookup->getLabel( $entityId );
		$desc = $langLookup->getDescription( $entityId );
		$result = [];

		if ( $label ) {
			$result[self::KEY_LABEL] = $label->getText();
		}

		if ( $desc ) {
			$result[self::KEY_DESC] = $desc->getText();
		}

		return $result;
	}

	/**
	 * Fetches 'has part' statements from a given item element with a defined lookup object for
	 * the right languages.
	 * @param Item $item
	 * @param LabelDescriptionLookup $langLookup
	 * @return iterable @see fetchSnaks
	 */
	private function fetchStatements( Item $item, LabelDescriptionLookup $langLookup ) {
		$statements = $item->getStatements();
		$statements = $statements->getByPropertyId( $this->propertyIdHasPart );
		return $this->fetchSnaks( $statements, $langLookup );
	}

	/**
	 * Fetches single snaks from 'has part' statements
	 * @param StatementList $statements the 'has part' statements
	 * @param LabelDescriptionLookup $langLookup
	 * @return iterable returns an array of all snaks. Each snak is an associated array with the keys
	 *      MathWikidataConnector::KEY_LABEL
	 *      MathWikidataConnector::KEY_DESC
	 *      MathWikidataConnector::KEY_SYMBOL
	 */
	private function fetchSnaks( StatementList $statements, LabelDescriptionLookup $langLookup ) {
		$result = [];
		foreach ( $statements as $statement ) {
			$snakResult = [];
			$snaks = $statement->getAllSnaks();

			foreach ( $snaks as $snak ) {
				if ( $snak instanceof PropertyValueSnak ) {
					if ( $this->isQualifierDefinien( $snak ) ) {
						$dataVal = $snak->getDataValue();
						$stringValue = new StringValue( $dataVal->getValue() );
						$math = $this->mathFormatter->format( $stringValue );
						$snakResult[self::KEY_SYMBOL] = $math;
					} elseif ( $snak->getPropertyId()->equals( $this->propertyIdHasPart ) ) {
						$dataVal = $snak->getDataValue();
						$entityIdValue = $dataVal->getValue();
						if ( $entityIdValue instanceof EntityIdValue ) {
							$innerEntityId = $entityIdValue->getEntityId();
							$labelDescRes = $this->fetchLabelDescription( $innerEntityId, $langLookup );
							$snakResult = array_merge( $snakResult, $labelDescRes );
						}
					}

				}
			}
			array_push( $result, $snakResult );
		}

		return $result;
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
	 * @return bool true if the given id is a valid QID
	 */
	public static function isValidQID( $qID ) {
		return preg_match( '/Q\d+/', $qID );
	}

	/**
	 * Generates an HTML string from the given data.
	 * @param string[] $info an info object generated by fetchWikidataFromId
	 * @return string an HTML representation of the given info object
	 */
	public static function buildHTMLRepresentation( $info ) {
		$output = Html::openElement( "div" );
		$output .= Html::openElement( "b" );
		$output .= $info[self::KEY_LABEL];
		$output .= Html::closeElement( "b" );

		$output .= " (" . $info[self::KEY_DESC] . ")";
		$output .= Html::closeElement( "div" );

		$output .= self::generateHTMLOfParts( $info[self::KEY_HASPART] );

		return $output;
	}

	/**
	 * @param array $parts
	 * @return string html
	 */
	private static function generateHTMLOfParts( $parts ) {
		$output = "";
		foreach ( $parts as $part ) {
			$output .= Html::openElement( "div" );
			if ( $part[self::KEY_SYMBOL] ) {
				$output .= Html::openElement( "b" );
				$output .= $part[self::KEY_SYMBOL];
				$output .= Html::closeElement( "b" );
				$output .= ": ";
			}

			$output .= Html::openElement( "i" );
			$output .= $part[self::KEY_LABEL];
			$output .= Html::closeElement( "i" );

			if ( $part[self::KEY_DESC] ) {
				$output .= " (" . $part[self::KEY_DESC] . ")";
			}

			$output .= Html::closeElement( "div" );
		}
		return $output;
	}

	/**
	 * @param array $info
	 * @param string $qid
	 * @param OutputPage $output
	 * @throws MWException
	 */
	public static function buildPageRepresentation( $info, $qid, OutputPage $output ) {
		$output->setPageTitle( $info[self::KEY_LABEL] );

		$desc = $info[self::KEY_DESC];
		preg_match( '/(.*):(.*)/', $desc, $matches );

		if ( $matches[1] ) {
			$output->setSubtitle( self::buildURL( $qid ) );
		}

		$output->addWikiTextAsInterface( self::buildURL( $qid ) . "" );

		$output->addWikiTextAsInterface( "== Formula Information ==" );
		$output->addWikiTextAsInterface( "'''Formula:''' " . $info[self::KEY_LABEL] );

		if ( $matches[1] ) {
			$output->addWikiTextAsInterface( "'''Type:''': " . $matches[1] );
			$output->addWikiTextAsInterface( "'''Description:''': " . $matches[2] );
		} else {
			$output->addWikiTextAsInterface( $info[self::KEY_DESC] );
		}

		$output->addWikiTextAsInterface( "== Elements of the Formula ==" );
		$output->addHTML( self::generateHTMLOfParts( $info[self::KEY_HASPART] ) );
	}

	/**
	 * @param string $qID
	 * @return string
	 */
	public static function buildURL( $qID ) {
		global $wgMathWikidataBaseUrl;
		return $wgMathWikidataBaseUrl . $qID;
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
