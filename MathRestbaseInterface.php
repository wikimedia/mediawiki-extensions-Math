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
		return $this->getContent( 'mml' );
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
		$postData = array(
			'type' => $this->type,
			'q'    => $this->tex
		);
		$requestResult = $this->makeRestbaseCheckRequest( $postData, $res );
		$json = json_decode( $res );
		if ( $requestResult ) {
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
	 * Performs a service request
	 * Generates error messages on failure
	 * @see Http::post()
	 *
	 * @param string $post the encoded post request
	 * @param mixed $res the result
	 * @return bool success
	 */
	private function makeRestbaseCheckRequest( $post, &$res ) {
		$res = null;
		$request = array(
			'method' => 'POST',
			'body'   => $post
		);
		$serviceClient = $this->getServiceClient();
		$request['url'] = $this->getUrl( "media/math/check/{$this->type}" );
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 ) {
			$res = $response['body'];
			$headers = $response['headers'];
			$this->hash = $headers['x-resource-location'];
			return true;
		} else {
			$res = $response['body'];
			$this->log()->info( 'Tex check failed:', array(
				'post'     => $post,
				'error'    => $response['error'],
				'url'      => $request['url']
			) );
			return false;
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
		$this->error = (object)array(
				'error' =>
						(object)array( 'message' => $msg )
		);
	}

}
