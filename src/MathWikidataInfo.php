<?php

use Wikibase\DataModel\Entity\EntityId;

/**
 * This class is a pojo of a mathematical wikidata item.
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
	 * @var string a symbol representing the item
	 */
	private $symbol;

	/**
	 * @var MathWikidataInfo[] hasparts
	 */
	private $hasParts = [];

	public function __construct( EntityId $entityId ) {
		$this->id = $entityId;
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
	 * @param string $symbol
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
	 * @return string symbol
	 */
	public function getSymbol() {
		return $this->symbol;
	}

	/**
	 * @return MathWikidataInfo[] hasparts
	 */
	public function getParts() {
		return $this->hasParts;
	}

	public function generateHTMLOfParts() {
		$output = "";
		foreach ( $this->hasParts as $part ) {
			$output .= Html::openElement( "div" );
			if ( $part->getSymbol() ) {
				$output .= $part->getSymbol();
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
