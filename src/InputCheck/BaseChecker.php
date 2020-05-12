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
	protected $inputTeX;
	protected $validTeX;
	protected $isValid = false;
	protected $lastError = null;

	/**
	 * @param string $tex the TeX InputString to be checked
	 */
	public function __construct( $tex = '' ) {
		$this->inputTeX = $tex;
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
		return $this->validTeX;
	}
}
