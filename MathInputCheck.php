<?php
/**
 * MediaWiki math extension
 *
<<<<<<< HEAD   (4bb5ca Style: Apply stylize.php)
 * (c) 2002-2013 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * @author Moritz Schubotz
 */
abstract class MathInputCheck {
	protected $inputTeX;
	protected $validTeX;
	protected $isValid = false;
	protected $lastError = '';

	/**
	 * Default contstructure
	 * (performs no checking)
	 * @param String $tex the TeX InputString to be checked
	 */
	public function __construct( $tex = '' ) {
		$this->inputTeX = $tex;
		$this->isValid = false;
	}

	/**
	 * Returns true if the TeX input String is valid
	 * @return boolean
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
	 * Some Tex checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the alterd tex string with this method
=======
 * (c) 2002-2014 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * @author Moritz Schubotz
 */
abstract class MathInputCheck {
	protected $inputTeX;
	protected $validTeX;
	protected $isValid = false;
	protected $lastError = null;

	/**
	 * Default constructor
	 * (performs no checking)
	 * @param String $tex the TeX InputString to be checked
	 */
	public function __construct( $tex = '' ) {
		$this->inputTeX = $tex;
		$this->isValid = false;
	}

	/**
	 * Returns true if the TeX input String is valid
	 * @return boolean
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
>>>>>>> BRANCH (6a0af8 Validate TeX input for all renderers, not just texvc)
	 * @return string A valid Tex string
	 */
	public function getValidTex() {
		return $this->validTeX;
	}
}
