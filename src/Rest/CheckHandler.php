<?php

namespace MediaWiki\Extension\Math\Rest;

use MediaWiki\Extension\Math\Backend\MathoidBackend;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use WANObjectCache;
use Wikimedia\ParamValidator\ParamValidator;

class CheckHandler extends SimpleHandler {
	private const VALID_TYPES = [ 'tex', 'inline-tex', 'chem' ];
	/** @var InputCheckFactory */
	private $checkerFactory;
	/** @var WANObjectCache	 */
	private $cache;

	public function __construct( WANObjectCache $cache,	InputCheckFactory $checkerFactory ) {
		$this->checkerFactory = $checkerFactory;
		$this->cache = $cache;
	}

	public function getParamSettings() {
		return [
			'type' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => self::VALID_TYPES,
				ParamValidator::PARAM_REQUIRED => true,
			],
			'q' => [
				self::PARAM_SOURCE => 'post',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	public function run( string $type ) : Response {
		$q = $this->getRequest()->getPostParams()['q'];
		$checker = $this->checkerFactory->newMathoidChecker( $q, $type );
		list( $statusCode, $content ) = $checker->getCheckResponse();
		$response = $this->getResponseFactory()->create();
		$response->setBody( new StringStream( $content ) );
		$response->setStatus( $statusCode );
		$response->setHeader( 'Content-Type', 'application/json' );
		if ( $statusCode === 200 ) {
			$hash = sha1( MathoidBackend::VERSION . '-' . $type . '-' . $content );
			$this->cache->set(
				$this->cache->makeGlobalKey( self::class, $hash ),
				[
					'q' => $content,
					'type' => $type
				],
				WANObjectCache::TTL_INDEFINITE
			);
			$response->setHeader( 'x-resource-location', $hash );
		}

		return $response;
	}

}
