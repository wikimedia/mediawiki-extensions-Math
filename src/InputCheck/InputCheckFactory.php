<?php

namespace MediaWiki\Extension\Math\InputCheck;

use Wikimedia\ObjectCache\WANObjectCache;

class InputCheckFactory {

	public function __construct(
		private readonly WANObjectCache $cache,
	) {
	}

	/**
	 * Create a BaseChecker instance, with the PHP variant of WikiTexVC.
	 *
	 * @param string $input input string to be checked
	 * @param string $type type of input (only 'tex')
	 * @param bool $purge whether to purge the cache
	 * @return LocalChecker checker based on php implementation of WikiTexVC within Math-extension
	 */
	public function newLocalChecker( string $input, string $type, bool $purge = false ): LocalChecker {
		return new LocalChecker(
			$this->cache,
			$input,
			$type,
			$purge
		);
	}
}
