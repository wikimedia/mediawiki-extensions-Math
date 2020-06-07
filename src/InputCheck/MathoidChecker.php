<?php

namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\Http\HttpRequestFactory;
use MWException;
use Psr\Log\LoggerInterface;
use WANObjectCache;

class MathoidChecker extends BaseChecker {

	private const EXPECTED_RETURN_CODES = [ 200, 400 ];
	private const VERSION = '1.0.0';
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
		$this->url = $url;
		$this->timeout = $timeout;
		$this->cache = $cache;
		$this->httpFactory = $httpFactory;
		$this->type = $type;
		$this->logger = $logger;
	}

	/**
	 * @return array
	 */
	public function getCheckResponse() : array {
		return $this->cache->getWithSetCallback(
			$this->getCacheKey(),
			WANObjectCache::TTL_INDEFINITE,
			[ $this, 'runCheck' ]
		);
	}

	/**
	 * @return string
	 */
	public function getCacheKey() : string {
		return $this->cache->makeGlobalKey(
			self::class,
			sha1( self::VERSION . '-' . $this->type . '-' . $this->inputTeX )
		);
	}

	/**
	 * @return array
	 * @throws MWException
	 */
	public function runCheck() : array {
		$url = "{$this->url}/texvcinfo";
		$q = rawurlencode( $this->inputTeX );
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
		$e = new MWException( 'Mathoid check returned unexpected error code.' );
		$this->logger->error( 'Mathoid check endpoint "{url}" returned ' .
			'HTTP status code "{statusCode}" for post data "{postData}": {exception}.',
			[
				'url' => $url,
				'statusCode' => $statusCode,
				'postData' => $postData,
				'exception' => $e,
			]
		);
		throw $e;
	}
}
