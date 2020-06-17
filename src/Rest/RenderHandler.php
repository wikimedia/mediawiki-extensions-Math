<?php

namespace MediaWiki\Extension\Math\Rest;

use MediaWiki\Extension\Math\Backend\MathoidBackendFactory;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\Stream;
use MediaWiki\Rest\StringStream;
use WANObjectCache;
use Wikimedia\Message\MessageValue;
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
	/** @var WANObjectCache */
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
		if ( $query === false ) {
			throw new LocalizedHttpException(
				new MessageValue( 'math-rest-nonexistent-hash', [ $hash ] ), 404 );
		}
		$backend = $this->backendFactory->newMathoidBackend( $query['q'], $query['type'] );
		list( $statusCode, $content ) = $backend->getResponse();
		$response = $this->getResponseFactory()->create();
		if ( $statusCode === 200 ) {
			$json = json_decode($content);
			$payload = $json->$format;
			foreach ( $payload->header as $key => $value ) {
				$response->setHeader( $key, $value );
			}
			if ($format === 'png'){
				$stream = fopen( 'php://memory', 'r+' );
				$response->setBody( new Stream( $stream ) );
				foreach ( $payload->body->data as $byte ) {
					fwrite( $stream, $byte );
				}
				fclose( $stream );
			} else {
				$response->setBody( new StringStream( $payload->body ) );
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
