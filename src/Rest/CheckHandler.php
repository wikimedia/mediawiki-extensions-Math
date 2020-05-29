<?php

namespace MediaWiki\Extension\Math\Rest;

use Config;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\Rest\ResponseFactory;
use MediaWiki\Rest\SimpleHandler;
use WANObjectCache;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Class CheckHandler extends the SimpleHandler while this is highly opinionated. The
 * SimpleHandler interface requires to return a ResponseFactory::createFromReturnValue() return
 * type, while Mathoid returns response Body and a status code. Thus depending on mathoids status
 * code different code paths needs to be takes. This is a bit wired but that's the way the
 * interface was designed.
 * @package MediaWiki\Extension\Math\Rest
 */
class CheckHandler extends SimpleHandler {
	private const VALID_TYPES = [ 'tex', 'inline-tex', 'chem' ];
	/** @var InputCheckFactory */
	private $checkerFactory;

	public function __construct(
		Config $config,
		WANObjectCache $cache,
		HttpRequestFactory $httpFactory
	) {
		$this->checkerFactory =	new InputCheckFactory(
			new ServiceOptions( InputCheckFactory::CONSTRUCTOR_OPTIONS, $config ),
			$cache,
			$httpFactory,
			LoggerFactory::getInstance( 'Math' )
		);
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

	/**
	 * @param string $type
	 * @return mixed something accepted by ResponseFactory::createFromReturnValue...
	 * @throws MathoidException
	 */
	public function run( $type ) {
		// SimpleHandler can not handle post params.
		$q = $this->getRequest()->getPostParams()['q'];
		$checker = $this->checkerFactory->newMathoidChecker( $q, $type );
		list( $statusCode, $content ) = $checker->getCheckResponse();
		if ( $statusCode == 200 ) {
			// Now we could just return the content, but we need to decode JSON in order
			// to let the SimpleHandler interface figure out that it's JSON. Then SimpleHandler
			// will convert it back to JSON.
			return json_decode($content);
		}
		// this is not a real exception it is just the only way to generate a custom body
		// for a non 200 response code note that this breaks backwards compatibility as the
		// we can not set fields like error in the response body. Unfortunately, it adds the
		// extra fields message and status, which are called error and httpCode in the mathoid
		// response.
		throw new MathoidException( $content, $statusCode );
	}

}
