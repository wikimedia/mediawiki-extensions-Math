<?php

namespace MediaWiki\Extension\Math\Rest;

use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use WANObjectCache;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class HashLookupHandler extends SimpleHandler {
	/** @var WANObjectCache	 */
	private $cache;

	public function __construct( WANObjectCache $cache ) {
		$this->cache = $cache;
	}

	public function getParamSettings() {
		return [
			'hash' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}

	public function run( string $hash ) : Response {
		$content = $this->cache->get( $this->cache->makeGlobalKey( CheckHandler::class, $hash ) );
		if ( $content === false ) {
			throw new LocalizedHttpException(
				new MessageValue( 'math-rest-nonexistent-hash', [ $hash ] ), 404 );
		}
		return $content;
	}

}
