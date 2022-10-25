<?php

namespace MediaWiki\Extension\Math\InputCheck;

use Exception;
use InvalidArgumentException;
use MediaWiki\Extension\Math\TexVC\TexVC;
use Message;

class LocalChecker extends BaseChecker {

	/** @var TexVC */
	private $texVC;
	/** @var array */
	private $result;

	/**
	 * @param string $tex the TeX input string to be checked
	 * @param string $type the input type, only tex allowed
	 * @throws InvalidArgumentException|Exception if the type is not correct.
	 */
	public function __construct( $tex = '', $type = 'tex' ) {
		parent::__construct( $tex );
		if ( $type === 'tex' || $type === 'chem' || $type === 'inline-tex' ) {
			$this->texVC = new TexVC();
			$options = $type === 'chem' ? [ "usemhchem" => true ] : null;
			$this->result = $this->texVC->check( $tex, $options );
		} else {
			throw new InvalidArgumentException( "Non supported type passed to LocalChecker: " . $type );
		}
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return $this->result["status"] === "+";
	}

	/**
	 * Some TeX checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the altered tex string with this method
	 * @return string|null A valid Tex string or null if no output
	 */
	public function getValidTex() {
		return array_key_exists( "output", $this->result ) ? $this->result["output"] : null;
	}

	/**
	 * Returns the string of the last error.
	 * @return ?Message
	 */
	public function getError(): ?Message {
		if ( $this->result["success"] ) {
			return null;
		}

		$errorOb = (object)[ "error" => null ];
		$errorOb->error = (object)$this->result["error"];
		return $this->errorObjectToMessage( $errorOb, "LocalCheck" );
	}
}
