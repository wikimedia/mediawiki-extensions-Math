<?php

use DataValues\StringValue;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * This class stores information about mathematical Wikidata items.
 */
class MathWikidataInfo {
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
	 * @var MathWikidataInfo[] hasparts
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
	 * @param MathWikidataInfo $info
	 */
	public function addHasPartElement( MathWikidataInfo $info ) {
		array_push( $this->hasParts, $info );
	}

	/**
	 * @param MathWikidataInfo[] $infos
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
	 * @return string|null the formatted version of the symbol
	 */
	public function getFormattedSymbol() {
		if ( $this->symbol ) {
			return $this->mathFormatter->format( $this->getSymbol() );
		} else {
			return null;
		}
	}

	/**
	 * @return MathWikidataInfo[] hasparts
	 */
	public function getParts() {
		return $this->hasParts;
	}

	/**
	 * Does this info object has elements?
	 * @return bool true if there are elements otherwise false
	 */
	public function hasParts() {
		if ( !$this->hasParts ) { return false;
		}
		return count( $this->hasParts ) > 0;
	}

	/**
	 * Generates a Wikitext representation of the has-parts elements
	 * @return string
	 */
	public function generateWikitextOfParts() {
		$output = "{| style='padding: 5px' ";
		foreach ( $this->hasParts as $part ) {
			$output .= "\n| style='text-align:right;' | '''" . $part->getLabel() . "'''";

			$output .= "\n| ";
			if ( $part->getSymbol() ) {
				$output .= "style='text-align:center; padding: 2px; " .
					"padding-left: 10px; padding-right: 10px;' | ";
				$formula = $part->getSymbol()->getValue();
				$output .= "<math> $formula </math>";
			}

			if ( $part->getDescription() ) {
				$output .= "\n|''" . $part->getDescription() . "''";
			} else {
				$output .= "\n|";
			}

			$output .= "\n|-";
		}
		return $output . "\n|}";
	}

	/**
	 * Generates a HTML representation of the has-parts elements
	 * @return string
	 */
	public function generateHTMLOfParts() {
		$output = "";
		foreach ( $this->hasParts as $part ) {
			$output .= Html::openElement( "div" );
			if ( $part->getSymbol() ) {
				$output .= $part->getFormattedSymbol();
				$output .= ": ";
			}

			$output .= Html::openElement( "i" );
			$output .= $part->getLabel();
			$output .= Html::closeElement( "i" );

			if ( $part->getDescription() ) {
				$output .= " (" . $part->getDescription() . ")";
			}

			$output .= Html::closeElement( "div" );
		}
		return $output;
	}
}
