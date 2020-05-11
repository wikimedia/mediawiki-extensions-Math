<?php

namespace MediaWiki\Extension\Math\Rest;

use MediaWiki\Extension\Math\InputCheck\MathoidChecker;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\Handler;
use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\StringStream;
use Wikimedia\Message\DataMessageValue;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Class Check
 * @package Math\Rest
 * A simple cache for the check endpoint of mathoid
 */
class Check extends Handler {
	private const VALID_TYPES = [ 'tex', 'inline-tex', 'chem' ];
	private $type;
	private $q;

	/**
	 * @return \MediaWiki\Rest\Response|\MediaWiki\Rest\ResponseInterface|null
	 * @throws LocalizedHttpException
	 */
	public function checkPreconditions() {
		$response = parent::checkPreconditions();
		if ( $response !== null ) {
			return $response;
		}
		$this->type = $this->getRequest()->getPathParam( 'type' );
		$this->q = $this->getRequest()->getPostParams()['q'];

		return $this->run();
	}

	/**
	 * @return \MediaWiki\Rest\Response
	 * @throws LocalizedHttpException
	 */
	private function run() {
		/** @var MathoidChecker $checker */
		$checker =
			MediaWikiServices::getInstance()
				->getService( 'MathCheckerFactory' )
				->getChecker( $this->q, $this->type );
		try {
			list( $statusCode, $content ) = $checker->getCheckResponse();
			$response = $this->getResponseFactory()->create();
			/** @noinspection PhpParamsInspection */
			$response->setBody( new StringStream( $content ) );
			$response->setStatus( $statusCode );

			return $response;
		}
		catch ( \MWException $e ) {
			$errMsg = new DataMessageValue( 'math_rest_check_error', [ $this->q ] );
			throw new LocalizedHttpException( $errMsg, 500, $e );
		}
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

	public function execute() {
		throw new \LogicException( 'Execute handler should never be called.' );
	}
}
