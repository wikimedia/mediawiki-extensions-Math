<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2013 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * @author Moritz Schubotz
 */
abstract class MathInputCheck {
	protected $inputTeX;
	protected $secureTeX;
	protected $isSecure = false;
	protected $lastError = '';

	/**
	 * Default contstructure
	 * (performs no checking)
	 * @param String $tex the TeX InputString to be checked
	 */
	public function __construct( $tex = '') {
		$this->inputTeX = $tex;
		$this->isSecure = false;
	}

	/**
	 * Returns true if the TeX input String is secure
	 * @return boolean
	 */
	public function isSecure(){
		return $this->isSecure;
	}

	/**
	 * Returns the string of the last error.
	 * @return string
	 */
	public function getError(){
		return $this->lastError;
	}

	/**
	 * Some Tex checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the alterd tex string with this method
	 * @return string A secure Tex string
	 */
	public function getSecureTex(){
		return $this->secureTeX;
	}
}
