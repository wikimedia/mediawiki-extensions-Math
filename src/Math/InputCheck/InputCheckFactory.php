<?php

namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Http\HttpRequestFactory;
use WANObjectCache;

class InputCheckFactory {
	public const CONSTRUCTOR_OPTIONS = [
		'MathMathMLUrl',
		'MathLaTeXMLTimeout',
	];
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

	public function __construct(
		ServiceOptions $options, WANObjectCache $cache, HttpRequestFactory $httpFactory
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->url = $options->get( 'MathMathMLUrl' );
		$this->timeout = $options->get( 'MathLaTeXMLTimeout' );
		$this->cache = $cache;
		$this->httpFactory = $httpFactory;
	}

	public function getChecker( $input, $type ) {
		return new MathoidChecker( $this->cache, $this->httpFactory,
			$this->url, $this->timeout,
			$input, $type );
	}
}
