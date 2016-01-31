<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2015 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

use MediaWiki\Logger\LoggerFactory;

class MathRestbaseInterface {
	private $hash = false;
	private $tex;
	private $type;
	private $checkedTex;
	private $success;
	private $identifiers;
	private $error;
	private $mathoidStyle;
	private $mml;

	/**
	 * MathRestbaseInterface constructor.
	 * @param string $tex
	 * @param string $type
	 */
	public function __construct( $tex = '', $type = 'tex' ) {
		$this->tex = $tex;
		$this->type = $type;
	}

	/**
	 * @return string MathML code
	 * @throws MWException
	 */
	public function getMathML() {
		if ( !$this->mml ){
			$this->mml = $this->getContent( 'mml' );
		}
		return $this->mml;
	}

	private function getContent( $type ) {
		$this->calculateHash();
		$request = array(
			'method' => 'GET',
			'url'    => $this->getUrl( "media/math/render/$type/{$this->hash}" )
		);
		$serviceClient = $this->getServiceClient();
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 ) {
			if ( array_key_exists( 'x-mathoid-style', $response['headers'] ) ) {
				$this->mathoidStyle =  $response['headers']['x-mathoid-style'];
			}
			return $response['body'];
		}
		$this->log()->error( 'Restbase math server problem:', array(
			'request'  => $request,
			'response' => $response,
			'type'     => $type,
			'tex'      => $this->tex
		) );
		throw new MWException( "Cannot get $type. Server problem." );
	}

	private function calculateHash() {
		if ( !$this->hash ) {
			if ( !$this->checkTeX() ) {
				throw new MWException( "TeX input is invalid." );
			}
		}
	}

	public function checkTeX() {
		$request = $this->getCheckRequest();
		$requestResult = $this->executeRestbaseCheckRequest( $request );
		return $this->evaluateRestbaseCheckResponse( $requestResult );
	}

	/**
	 * Performs a service request
	 * Generates error messages on failure
	 * @see Http::post()
	 *
	 * @param array $request the request object
	 * @return bool success
	 */
	private function executeRestbaseCheckRequest( $request ) {
		$res = null;
		$serviceClient = $this->getServiceClient();
		$response =  $serviceClient->run( $request );
		if ( $response['code'] !== 200 ) {
			$this->log()->info( 'Tex check failed:', array(
					'post'  => $request['body'],
					'error' => $response['error'],
					'url'   => $request['url']
			) );
		}
		return $response;

	}

	/**
	 * @param array $rbis array of MathRestbaseInterface instances
	 */
	public static function batchEvaluate( $rbis ) {
		if ( count( $rbis ) == 0 ){
			return;
		}
		$requests = array();
		/** @var MathRestbaseInterface $first */
		$first = $rbis[0];
		$serviceClient = $first->getServiceClient();
		foreach ( $rbis as $rbi ) {
			/** @var MathRestbaseInterface $rbi */
			$requests[] = $rbi->getCheckRequest();
		}
		$results = $serviceClient->runMulti( $requests );
		$i = 0;
		foreach ( $results as $response ) {
			/** @var MathRestbaseInterface $rbi */
			$rbi = $rbis[$i ++];
			$rbi->evaluateRestbaseCheckResponse( $response );
		}

	}

	private function getServiceClient() {
		global $wgVirtualRestConfig;
		$serviceClient = new VirtualRESTServiceClient( new MultiHttpClient( array() ) );
		if ( isset( $wgVirtualRestConfig['modules']['restbase'] ) ) {
			$cfg = $wgVirtualRestConfig['modules']['restbase'];
			$cfg['parsoidCompat'] = false;
			$vrsObject = new RestbaseVirtualRESTService( $cfg );
			$serviceClient->mount( '/mathoid/', $vrsObject );
		}
		return $serviceClient;
	}


	/**
	 * The URL is generated accoding to the following logic:
	 *
	 * Case A: <code>$internal = false</code>, which means one needs an URL that is accessible from
	 * outside:
	 *
	 * --> If <code>$wgMathFullRestbaseURL</code> is configured use it, otherwise fall back try to
	 * <code>$wgVisualEditorFullRestbaseURL</code>. (Note, that this is not be worse than failing
	 * immediately.)
	 *
	 * Case B: <code> $internal= true</code>, which means one needs to access content from Restbase
	 * which does not need to be accessible from outside:
	 *
	 * --> Use the mount point whenever possible. If the mount point is not available, use
	 * <code>$wgMathFullRestbaseURL</code> with fallback to <code>wgVisualEditorFullRestbaseURL</code>
	 *
	 * @param string $path
	 * @param bool|true $internal
	 * @return string
	 * @throws MWException
	 */
	public function getUrl( $path, $internal = true ) {
		global $wgVirtualRestConfig, $wgMathFullRestbaseURL, $wgVisualEditorFullRestbaseURL;
		if ( $internal && isset( $wgVirtualRestConfig['modules']['restbase'] ) ) {
			return "/mathoid/local/v1/$path";
		}
		if ( $wgMathFullRestbaseURL ) {
			return "{$wgMathFullRestbaseURL}v1/$path";
		}
		if ( $wgVisualEditorFullRestbaseURL ) {
			return "{$wgVisualEditorFullRestbaseURL}v1/$path";
		}
		$msg = 'Math extension can not find Restbase URL. Please specify $wgMathFullRestbaseURL.';
		$this->setErrorMessage( $msg );
		throw new MWException( $msg );
	}

	/**
	 * @return \Psr\Log\LoggerInterface
	 */
	private function log() {
		return LoggerFactory::getInstance( 'Math' );
	}

	public function getSvg() {
		return $this->getContent( 'svg' );
	}

	/**
	 * @param bool|false $skipConfigCheck
	 * @return bool
	 */
	public function checkBackend( $skipConfigCheck = false ) {
		try {
			$request = array(
				'method' => 'GET',
				'url'    => $this->getUrl( '?spec' )
			);
		} catch ( Exception $e ) {
			return false;
		}
		$serviceClient = $this->getServiceClient();
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 ) {
			return $skipConfigCheck || $this->checkConfig();
		}
		$this->log()->error( "Restbase backend is not correctly set up.", array(
			'request'  => $request,
			'response' => $response
		) );
		return false;
	}

	/**
	 * Generates a unique TeX string, renders it and gets it via a public URL.
	 * The method fails, if the public URL does not point to the same server, who did render
	 * the unique TeX input in the first place.
	 * @return bool
	 */
	private function checkConfig() {
		// Generates a TeX string that probably has not been generated before
		$uniqueTeX = uniqid( 't=', true );
		$testInterface = new MathRestbaseInterface( $uniqueTeX );
		if ( ! $testInterface->checkTeX() ){
			$this->log()->warning( 'Config check failed, since test expression was considered as invalid.',
				array( 'uniqueTeX' => $uniqueTeX ) );
			return false;
		}
		try {
			$url = $testInterface->getFullSvgUrl();
			$req = MWHttpRequest::factory( $url );
			$status = $req->execute();
			if ( $status->isOK() ){
				return true;
			}
			$this->log()->warning( 'Config check failed, due to an invalid response code.',
				array( 'responseCode' => $status ) );
		} catch ( Exception $e ) {
			$this->log()->warning( 'Config check failed, due to an exception.', array( $e ) );
			return false;
		}
	}

	/**
	 * Gets a publicly accessible link to the generated SVG image.
	 * @return string
	 * @throws MWException
	 */
	public function getFullSvgUrl() {
		$this->calculateHash();
		return $this->getUrl( "media/math/render/svg/{$this->hash}", false );
	}

	/**
	 * @return string
	 */
	public function getCheckedTex() {
		return $this->checkedTex;
	}

	/**
	 * @return boolean
	 */
	public function getSuccess() {
		if ( $this->success === null ) {
			$this->checkTeX();
		}
		return $this->success;
	}

	/**
	 * @return array
	 */
	public function getIdentifiers() {
		return $this->identifiers;
	}

	/**
	 * @return stdClass
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @return string
	 */
	public function getTex() {
		return $this->tex;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	private function setErrorMessage( $msg ) {
		$this->error = (object)array( 'error' => (object)array( 'message' => $msg ) );
	}

	/**
	 * @return array
	 * @throws MWException
	 */
	public function getCheckRequest() {
		$request = array(
				'method' => 'POST',
				'body'   => array(
						'type' => $this->type,
						'q'    => $this->tex
				),
				'url'    => $this->getUrl( "media/math/check/{$this->type}" )
		);
		return $request;
	}

	/**
	 * @param $response
	 * @return bool
	 */
	public function evaluateRestbaseCheckResponse( $response ) {
		$json = json_decode( $response['body'] );
		if ( $response['code'] === 200 ) {
			$headers = $response['headers'];
			$this->hash = $headers['x-resource-location'];
			$this->success = $json->success;
			$this->checkedTex = $json->checked;
			$this->identifiers = $json->identifiers;
			return true;
		} else {
			if ( isset( $json->detail ) && isset( $json->detail->success ) ) {
				$this->success = $json->detail->success;
				$this->error = $json->detail;
			} else {
				$this->success = false;
				$this->setErrorMessage( 'Math extension cannot connect to Restbase.' );
			}
			return false;
		}
	}

	/**
	 * @return mixed
	 */
	public function getMathoidStyle() {
		return $this->mathoidStyle;
	}
}
