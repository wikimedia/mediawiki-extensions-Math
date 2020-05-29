<?php

namespace MediaWiki\Extension\Math\Rest;

use MediaWiki\Rest\HttpException;

class MathoidException extends HttpException {

	/**
	 * MathoidException constructor.
	 * @param mixed $content
	 * @param mixed $statusCode
	 */
	public function __construct( $content, $statusCode ) {
		$errData = json_decode( $content );
		parent::__construct( (string)$errData->error, $statusCode, $errData );
	}
}
