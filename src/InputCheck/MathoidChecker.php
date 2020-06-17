<?php

namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\Extension\Math\Backend\MathoidBackend;
use MediaWiki\Http\HttpRequestFactory;
use Psr\Log\LoggerInterface;
use WANObjectCache;

class MathoidChecker extends BaseChecker {
	/** @var WANObjectCache */
	private $cache;
	/** @var MathoidBackend */
	private $backend;

	/**
	 * @param WANObjectCache $cache
	 * @param HttpRequestFactory $httpFactory
	 * @param LoggerInterface $logger
	 * @param String $url
	 * @param int $timeout
	 * @param string $input
	 * @param string $type
	 */
	public function __construct(
		WANObjectCache $cache,
		HttpRequestFactory $httpFactory,
		LoggerInterface $logger,
		$url,
		$timeout,
		string $input,
		string $type
	) {
		parent::__construct( $input );
		$this->backend = new MathoidBackend(
			$cache,
			$httpFactory,
			$logger,
			$url,
			$timeout,
			$input,
			$type,
			MathoidBackend::ENDPOINT_CHECK
		);
	}

	/**
	 * @return array
	 */
	public function getCheckResponse() : array {
		return $this->backend->getResponse();
	}

	/**
	 * @return string
	 */
	public function getCacheKey() : string {
		return $this->backend->getCacheKey();
	}
}
