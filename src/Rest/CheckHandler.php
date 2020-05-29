<?php

namespace MediaWiki\Extension\Math\Rest;

use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\Rest\StringStream;
use Wikimedia\ParamValidator\ParamValidator;

class CheckHandler extends SimpleHandler {
	private const VALID_TYPES = [ 'tex', 'inline-tex', 'chem' ];
	/** @var InputCheckFactory */
	private $checkerFactory;

	public function __construct( InputCheckFactory $checkerFactory ) {
		$this->checkerFactory = $checkerFactory;
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
		return $response;
	}

}
