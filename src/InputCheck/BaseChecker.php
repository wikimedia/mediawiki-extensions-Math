<?php

namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\Message\Message;

/**
 * MediaWiki math extension
 *
 * @copyright 2002-2014 Tomasz Wegrzanowski, Brooke Vibber, Moritz Schubotz,
 * and other MediaWiki contributors
 * @license GPL-2.0-or-later
 * @author Moritz Schubotz
 */
abstract class BaseChecker {
	protected ?string $validTeX = null;
	/** @var bool */
	protected $isValid = false;

	/**
	 * @param string $inputTeX the TeX InputString to be checked
	 * @param bool $purge if true, the cache will be purged
	 */
	public function __construct(
		protected readonly string $inputTeX = '',
		protected bool $purge = false,
	) {
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
	 * @return ?Message
	 */
	abstract public function getError(): ?Message;

	/**
	 * Some TeX checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the altered tex string with this method
	 * @return ?string A valid Tex string
	 */
	public function getValidTex(): ?string {
		return $this->validTeX;
	}

	public function setPurge( bool $purge ) {
		$this->purge = $purge;
	}
}
