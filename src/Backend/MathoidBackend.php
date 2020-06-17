<?php

namespace MediaWiki\Extension\Math\Backend;

use MediaWiki\Http\HttpRequestFactory;
use MWException;
use Psr\Log\LoggerInterface;
use WANObjectCache;

class MathoidBackend {

	private const EXPECTED_RETURN_CODES = [ 200, 400 ];
	public const VERSION = '1.0.0';
	/** @var string */
	private $url;
	/** @var int */
	private $timeout;
	/** @var WANObjectCache */
	private $cache;
	/** @var HttpRequestFactory */
	private $httpFactory;
	/** @var string */
	private $type;
	/** @var LoggerInterface */
	private $logger;
	/** @var string */
	private $input;
	/** @var string */
	private $endpoint;

	/**
	 * @param WANObjectCache $cache
	 * @param HttpRequestFactory $httpFactory
	 * @param LoggerInterface $logger
	 * @param String $url
	 * @param int $timeout
	 * @param string $input
	 * @param string $type
	 * @param string $endpoint
	 */
	public function __construct(
		WANObjectCache $cache,
		HttpRequestFactory $httpFactory,
		LoggerInterface $logger,
		$url,
		$timeout,
		string $input,
		string $type,
		string $endpoint
	) {
		$this->input = $input;
		$this->url = $url;
		$this->timeout = $timeout;
		$this->cache = $cache;
		$this->httpFactory = $httpFactory;
		$this->type = $type;
		$this->logger = $logger;
		$this->endpoint = $endpoint;
	}

	/**
	 * @return array
	 */
	public function getResponse() : array {
		return $this->cache->getWithSetCallback(
			$this->getCacheKey(),
			WANObjectCache::TTL_INDEFINITE,
			[ $this, 'run' ]
		);
	}

	/**
	 * @return string
	 */
	public function getCacheKey() : string {
		return $this->cache->makeGlobalKey(
			self::class,
			sha1( self::VERSION .
				'-' . $this->endpoint .
				'-' . $this->type .
				'-' . $this->input )
		);
	}

	/**
	 * @return array
	 * @throws MWException
	 */
	public function run() : array {
		$url = "{$this->url}/{$this->endpoint}";
		$q = rawurlencode( $this->input );
		$postData = "type=$this->type&q=$q";
		$options = [
			'method' => 'POST',
			'postData' => $postData,
			'timeout' => $this->timeout,
		];
		$req = $this->httpFactory->create( $url, $options, __METHOD__ );
		$req->execute();
		$statusCode = $req->getStatus();
		if ( in_array( $statusCode, self::EXPECTED_RETURN_CODES ) ) {
			return [ $statusCode, $req->getContent() ];
		}
		$e = new MWException( 'Mathoid returned unexpected error code.' );
		$this->logger->error( 'Mathoid {complete} endpoint "{url}" returned ' .
			'HTTP status code "{statusCode}" for post data "{postData}": {exception}.',
			[
				'endpoint' => $this->endpoint,
				'url' => $url,
				'statusCode' => $statusCode,
				'postData' => $postData,
				'exception' => $e,
			]
		);
		throw $e;
	}

	public function getType() {
		return $this->type;
	}
}
