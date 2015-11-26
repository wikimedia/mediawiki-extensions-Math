<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2015 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

use MediaWiki\Logger\LoggerFactory;

class MathRestBaseInterface {
	private $hash = false;
	private $tex;
	private $type;
	private $checkedTex;
	private $success;
	private $identifiers;
	private $error;

	/**
	 * MathRestBaseInterface constructor.
	 * @param string $tex
	 * @param bool $displayStyle
	 */
	public function __construct( $tex = '', $displayStyle = true ) {
		$this->tex = $tex;
		if ( $displayStyle ) {
			$this->type = 'tex';
		} else {
			$this->type = 'inline-TeX';
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
		$serviceClient = $this->getServiceClient( $request, "media/math/check/{$this->type}" );
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 && $response['error'] === "" ) {
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

	private function getServiceClient( &$request, $path ) {
		$serviceClient = new VirtualRESTServiceClient( new MultiHttpClient( array() ) );
		global $wgMathRestUrl, $wgVirtualRestConfig;
		if ( is_array( $wgVirtualRestConfig ) &&
			isset( $wgVirtualRestConfig['modules']['restbase'] )
		) {
			$cfg = $wgVirtualRestConfig['modules']['restbase'];
			$cfg['parsoidCompat'] = false;
			$vrsObject = new RestbaseVirtualRESTService( $cfg );
			$serviceClient->mount( '/mathoid/', $vrsObject );
			$request['url'] = "/mathoid/local/v1/$path";
		} else {
			$request['url'] = "$wgMathRestUrl/$path";
		}
		return $serviceClient;
	}

	public function checkBackend() {
		$request = array(
			'method' => 'GET'
		);
		$serviceClient = $this->getServiceClient( $request, 'media/math/check/?spec' );
		$response = $serviceClient->run( $request );
		if ( $response['code'] === 200 && $response['error'] === "" ) {
			return true;
		}
		$logger = LoggerFactory::getInstance( 'Math' );
		$logger->error( "RestBase backend not not correctly set up.", array(
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
