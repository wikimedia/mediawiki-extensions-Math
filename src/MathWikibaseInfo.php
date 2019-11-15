<?php

use DataValues\StringValue;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * This class stores information about mathematical Wikibase items.
 */
class MathWikibaseInfo {
	/**
	 * @var EntityId
	 */
	private $id;

	/**
	 * @var string the label of the item
	 */
	private $label;

	/**
	 * @var string description of the item
	 */
	private $description;

	/**
	 * @var StringValue a symbol representing the item
	 */
	private $symbol;

	/**
	 * @var MathWikibaseInfo[]
	 */
	private $hasParts = [];

	/**
	 * @var MathFormatter
	 */
	private $mathFormatter;

	public function __construct( EntityId $entityId ) {
		$this->id = $entityId;
		$this->mathFormatter = new MathFormatter( SnakFormatter::FORMAT_HTML );
	}

	/**
	 * @param string $label
	 */
	public function setLabel( $label ) {
		$this->label = $label;
	}

	/**
	 * @param string $description
	 */
	public function setDescription( $description ) {
		$this->description = $description;
	}

	/**
	 * @param StringValue $symbol
	 */
	public function setSymbol( $symbol ) {
		$this->symbol = $symbol;
	}

	/**
	 * @param MathWikibaseInfo $info
	 */
	public function addHasPartElement( MathWikibaseInfo $info ) {
		array_push( $this->hasParts, $info );
	}

	/**
	 * @param MathWikibaseInfo[] $infos
	 */
	public function addHasPartElements( $infos ) {
		array_push( $this->hasParts, ...$infos );
	}

	/**
	 * @return EntityId id
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string label
	 */
	public function getLabel() {
		return $this->label;
	}

	/**
	 * @return string description
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * @return StringValue symbol
	 */
	public function getSymbol() {
		return $this->symbol;
	}

	/**
	 * @return string|null html formatted version of the symbol
	 */
	public function getFormattedSymbol() {
		if ( $this->symbol ) {
			return $this->mathFormatter->format( $this->getSymbol() );
		} else {
			return null;
		}
	}

	/**
	 * @return MathWikibaseInfo[] hasparts
	 */
	public function getParts() {
		return $this->hasParts;
	}

	/**
	 * Does this info object has elements?
	 * @return bool true if there are elements otherwise false
	 */
	public function hasParts() {
		if ( !$this->hasParts ) {
			return false;
		}
		return $this->hasParts !== [];
	}

	/**
	 * Generates an HTML table representation of the has-parts elements
	 * @return string
	 */
	public function generateTableOfParts() {
		$lang = MediaWikiServices::getInstance()->getContentLanguage();
		$labelAlign = $lang->isRTL() ? 'left' : 'right';
		$labelAlignOpposite = !$lang->isRTL() ? 'left' : 'right';

		$output = HTML::openElement( "table", [ "style" => "padding: 5px" ] );
		$output .= HTML::openElement( "tbody" );

		foreach ( $this->hasParts as $part ) {
			$output .= HTML::openElement( "tr" );
			$output .= HTML::element(
				"td",
				[ "style" => "font-weight: bold; text-align:$labelAlign;" ],
				$part->getLabel()
			);
			$output .= HTML::rawElement(
				"td",
				[ "style" => "text-align:center; padding: 2px; padding-left: 10px; padding-right: 10px;" ],
				$part->getFormattedSymbol()
			);
			$output .= HTML::element(
				"td",
				[ "style" => "font-style: italic; text-align:$labelAlignOpposite;" ],
				$part->getDescription()
			);
			$output .= HTML::closeElement( "tr" );
		}

		$output .= HTML::closeElement( "tbody" );
		$output .= HTML::closeElement( "table" );
		return $output;
	}

	/**
	 * Generates a minimalized HTML representation of the has-parts elements.
	 * @return string
	 */
	public function generateSmallTableOfParts() {
		$output = HTML::openElement( "table" );
		$output .= HTML::openElement( "tbody" );

		foreach ( $this->hasParts as $part ) {
			$output .= HTML::openElement( "tr" );
			$output .= HTML::rawElement(
				"td",
				[ "style" => "text-align:right;" ],
				$part->getFormattedSymbol()
			);
			$output .= HTML::element( "td", [ "style" => "text-align:left;" ], $part->getLabel() );
			$output .= HTML::closeElement( "tr" );
		}

		$output .= HTML::closeElement( "tbody" );
		$output .= HTML::closeElement( "table" );
		return $output;
	}
}
