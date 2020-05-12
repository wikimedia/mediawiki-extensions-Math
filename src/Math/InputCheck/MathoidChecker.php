<?php

namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\Http\HttpRequestFactory;
use MWException;
use WANObjectCache;

class MathoidChecker extends BaseChecker {

	protected $version = '1.0.0';
	private const EXPECTED_RETURN_CODES = [ 200, 400 ];
	private $url;
	private $timeout;
	/**
	 * @var WANObjectCache
	 */
	private $cache;
	/**
	 * @var HttpRequestFactory
	 */
	private $httpFactory;
	private $type;

	public function __construct(
		WANObjectCache $cache, HttpRequestFactory $httpFactory,
		$url, $timeout,
		string $input,
		string $type
	) {
		parent::__construct( $input );
		$this->url = $url;
		$this->timeout = $timeout;
		$this->cache = $cache;
		$this->httpFactory = $httpFactory;
		$this->type = $type;
	}

	public function getCacheKey() {
		return $this->cache->makeGlobalKey( self::class, $this->version, $this->type,
			$this->inputTeX );
	}

	public function getCheckResponse() {
		return $this->cache->getWithSetCallback( $this->getCacheKey(),
			WANObjectCache::TTL_INDEFINITE, [ $this, 'runCheck' ] );
	}

	/**
	 * @return array|[int,string]
	 * @throws MWException
	 */
	public function runCheck() {
		$url = "$this->url/texvcinfo";
		$q = rawurlencode( $this->inputTeX );
		$options = [
			'method' => 'POST',
			'postData' => "type=$this->type&q=$q",
			'timeout' => $this->timeout,
		];
		$req = $this->httpFactory->create( $url, $options );
		$req->execute();
		$statusCode = $req->getStatus();
		if ( in_array( $statusCode, self::EXPECTED_RETURN_CODES ) ) {
			return [ $statusCode, $req->getContent() ];
		} else {
			throw new MWException( 'Mathoid check request failed' );
		}
	}
}
