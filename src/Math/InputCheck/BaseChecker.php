<?php

namespace MediaWiki\Extension\Math\InputCheck;

/**
 * MediaWiki math extension
 *
 * @copyright 2002-2014 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz,
 * and other MediaWiki contributors
 * @license GPL-2.0-or-later
 * @author Moritz Schubotz
 */
abstract class BaseChecker {
	protected $input;
	protected $validInput;
	protected $type;
	protected $version = null;
	protected $isValid = false;
	protected $lastError = null;

	public function __construct( $tex = '', $type = 'tex' ) {
		$this->input = $tex;
		$this->type = $type;
		$this->isValid = false;
	}

	/**
	 * Returns true if the TeX input String is valid
	 * @return bool
	 */
	public function isValid() {
		return $this->isValid;
	}

	/**
	 * Returns the string of the last error.
	 * @return string
	 */
	public function getError() {
		return $this->lastError;
	}

	/**
	 * Some TeX checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the altered tex string with this method
	 * @return string A valid Tex string
	 */
	public function getValidTex() {
		return $this->validInput;
	}
}
