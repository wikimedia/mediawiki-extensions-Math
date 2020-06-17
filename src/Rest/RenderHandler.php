<?php

namespace MediaWiki\Extension\Math\Rest;

use MediaWiki\Extension\Math\Backend\MathoidBackendFactory;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use WANObjectCache;
use Wikimedia\ParamValidator\ParamValidator;

class RenderHandler extends SimpleHandler {
	private const VALID_FORMATS = [ 'svg', 'mml', 'png' ];

	private const MML_TYPE = 'application/mathml+xml; ' .
		'charset=utf-8; profile="https://www.mediawiki.org/wiki/Specs/MathML/1.0.0"';
	private const SVG_TYPE = 'image/svg+xml; ' .
		'charset=utf-8; profile="https://www.mediawiki.org/wiki/Specs/SVG/1.0.0"';
	private const PNG_TYPE = 'image/png; charset=utf-8; ' .
		'profile="https://www.mediawiki.org/wiki/Specs/PNG/1.0.0';
	/** @var MathoidBackendFactory */
	private $backendFactory;
	/** @var WANObjectCache	 */
	private $cache;

	public function __construct( MathoidBackendFactory $factory, WANObjectCache $cache ) {
		$this->backendFactory = $factory;
		$this->cache = $cache;
	}

	public function getParamSettings() {
		return [
			'format' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => self::VALID_FORMATS,
				ParamValidator::PARAM_REQUIRED => true,
			],
			'hash' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
		];
	}

	public function run( string $format, string $hash ) : Response {
		$query = $this->cache->get( $this->cache->makeGlobalKey( CheckHandler::class, $hash ) );
		$backend = $this->backendFactory->newMathoidBackend( $query->q, $query->type );
		list( $statusCode, $content ) = $backend->getResponse();
		$response = $this->getResponseFactory()->create();
		if ( $statusCode === 200 ) {
			switch ( $format ) {
				case 'mml':
					$response->setBody( new StringStream( $content->mml ) );
					$response->setHeader( 'Content-Type', self::MML_TYPE );
					$response->setHeader( 'x-mathoid-style', $content->mathoidStyle );
					break;
				case 'svg':
					$response->setBody( new StringStream( $content->svg ) );
					$response->setHeader( 'Content-Type', self::SVG_TYPE );
					return $content->svg;
				case 'png':
					$response->setHeader( 'Content-Type', self::PNG_TYPE );
					return $content->png;
			}
		} else {
			$response->setBody( new StringStream( $content ) );
			$response->setHeader( 'Content-Type', 'application/json' );
		}
		$response->setStatus( $statusCode );
		$response->setHeader( 'x-resource-location', $hash );
		return $response;
	}

}
