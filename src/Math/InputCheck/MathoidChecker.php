<?php
namespace MediaWiki\Extension\Math\InputCheck;

use MediaWiki\MediaWikiServices;
use MWException;
use WANObjectCache;

class MathoidChecker extends BaseChecker {
	protected $version = '1.0.0';
	private const EXPECTED_RETURN_CODES = [ 200, 400 ];

	public function getCacheKey() {
		$bagOStuff = MediaWikiServices::getInstance()->getMainWANObjectCache();

		return $bagOStuff->makeGlobalKey( self::class, $this->version, $this->type, $this->input );
	}

	public function getCheckResponse() {
		$bagOStuff = MediaWikiServices::getInstance()->getMainWANObjectCache();
		return $bagOStuff->getWithSetCallback( $this->getCacheKey(), WANObjectCache::TTL_INDEFINITE,
			[ $this, 'runCheck' ] );
	}

	public function runCheck() {
		global $wgMathMathMLUrl, $wgMathLaTeXMLTimeout;
		$url = "$wgMathMathMLUrl/texvcinfo";
		$q = rawurlencode( $this->input );
		$options = [
			'method' => 'POST',
			'postData' => "type=$this->type&q=$q",
			'timeout' => $wgMathLaTeXMLTimeout,
		];
		$req = MediaWikiServices::getInstance()->getHttpRequestFactory()->create( $url, $options );
		$req->execute();
		$statusCode = $req->getStatus();
		if ( in_array( $statusCode, self::EXPECTED_RETURN_CODES ) ) {
			return [ $statusCode, $req->getContent() ];
		} else {
			throw new MWException( 'Mathoid check request failed' );
		}
	}
}
