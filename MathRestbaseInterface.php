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
	 * @param bool $displayStyle
	 */
	public function __construct( $tex = '', $displayStyle = true ) {
		$this->tex = $tex;
		if ( $displayStyle ) {
			$this->type = 'tex';
		} else {
			$this->type = 'inline-tex';
		}
	}

	public function checkTeX() {
		$postData = array(
			'type' => $this->type,
			'q'    => $this->tex
		);
		$requestResult = $this->makeRestbaseRequest( $postData, $res );
		$json = json_decode( $res );
		if ( $requestResult ) {
			$this->success = $json->success;
			$this->checkedTex = $json->checked;
			$this->identifiers = $json->identifiers;
			return true;
		} else {
			$this->success = $json->detail->success;
			$this->error = $json->detail;
			return false;
		}
	}

	private function getContent( $type ) {
		if ( !$this->hash ) {
			if ( !$this->checkTeX() ) {
				throw new MWException( "Cannot get $type. TeX input is invalid." );
			}
		}
		$request = array(
			'method' => 'GET',
			'url'    => self::getUrl( "media/math/render/$type/{$this->hash}" )
		);
		$serviceClient = $this->getServiceClient();
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 ) {
			return $response['body'];
		}
		LoggerFactory::getInstance( 'Math' )->error( 'Restbase math server problem:', array(
			'request'  => $request,
			'response' => $response,
			'type'     => $type,
			'tex'      => $this->tex
		) );
		throw new MWException( "Cannot get $type. Server problem." );
	}

	public function getMathML() {
		return $this->getContent( 'mml' );
	}

	public function getSvg() {
		return $this->getContent( 'svg' );
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
	private function makeRestbaseRequest( $post, &$res ) {
		$res = null;
		$request = array(
			'method' => 'POST',
			'body'   => $post
		);
		$serviceClient = $this->getServiceClient();
		$request['url'] = self::getUrl( "media/math/check/{$this->type}" );
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 ) {
			$res = $response['body'];
			$headers = $response['headers'];
			$this->hash = $headers['x-resource-location'];
			return true;
		} else {
			$errormsg = $response['error'];
			$res = $response['body'];
			LoggerFactory::getInstance( 'Math' )->debug( 'Tex check failed:', array(
				'post'     => $post,
				'errormsg' => $errormsg,
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

	private static function getUrl( $path, $internal = true ) {
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
		throw new MWException( 'Math extension can not find Restbase URL.'.
			' Please specify $wgMathFullRestbaseURL.' );
	}

	public function checkBackend() {
		try {
			$request = array(
				'method' => 'GET',
				'url'    => self::getUrl( '?spec' )
			);
		} catch ( Exception $e ) {
			return false;
		}
		$serviceClient = $this->getServiceClient();
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 ) {
			return true;
		}
		$logger = LoggerFactory::getInstance( 'Math' );
		$logger->error( "Restbase backend is not correctly set up.", array(
			'request'  => $request,
			'response' => $response
		) );
		return false;
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

}
