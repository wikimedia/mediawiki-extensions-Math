<?php

/**
 * MediaWiki math extension
 *
 * (c)2012 Moritz Schubotz
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the LaTeXML daemon
 * @file
 */
class MathLaTeXML extends MathMathML {

	/**
	 * @var String settings for LaTeXML daemon
	 */
	private $LaTeXMLSettings = '';
	/** @var boolean if false LaTeXML output is not validated*/
	private $XMLValidation = true;
	/**
	 * Converts an array with LaTeXML settings to a URL encoded String.
	 * If the argument is a string the input will be returned.
	 * Thus the function has projector properties and can be applied a second time safely.
	 * @param (string|array) $array
	 * @return string
	 */
	public function serializeSettings( $array ) {
		if ( !is_array( $array ) ) {
			return $array;
		} else {
			// removes the [1] [2]... for the unnamed subarrays since LaTeXML
			// assigns multiple values to one key e.g.
			// preload=amsmath.sty&preload=amsthm.sty&preload=amstext.sty
			$cgi_string = wfArrayToCgi( $array );
			$cgi_string = preg_replace( '|\%5B\d+\%5D|', '', $cgi_string );
			$cgi_string = preg_replace( '|&\d+=|', '&', $cgi_string );
			return $cgi_string;
		}
	}

	/**
	 * Gets the settings for the LaTeXML daemon.
	 *
	 * @return string
	 */
	public function getLaTeXMLSettings() {
		global $wgMathDefaultLaTeXMLSetting;
		if ( $this->LaTeXMLSettings ) {
			return $this->LaTeXMLSettings;
		} else {
			return $wgMathDefaultLaTeXMLSetting;
		}
	}

	/**
	 * Sets the settings for the LaTeXML daemon.
	 * The settings affect only the current instance of the class.
	 * For a list of possible settings see:
	 * http://dlmf.nist.gov/LaTeXML/manual/commands/latexmlpost.xhtml
	 * An empty value indicates to use the default settings.
	 * @param string|array $settings
	 */
	public function setLaTeXMLSettings( $settings ) {
		$this->LaTeXMLSettings = $settings;
	}

	/**
	 * Gets the allowed root elements the rendered math tag might have.
	 *
	 * @return array
	 */
	public function getAllowedRootElements() {
		if ( $this->allowedRootElements ) {
			return $this->allowedRootElements;
		} else {
			return self::$DEFAULT_ALLOWED_ROOT_ELEMENTS;
		}
	}

	/**
	 * Sets the allowed root elements the rendered math tag might have.
	 * An empty value indicates to use the default settings.
	 * @param array $settings
	 */
	public function setAllowedRootElments( $settings ) {
		$this->allowedRootElements = $settings;
	}

	/**
	 * Picks a LaTeXML daemon.
	 * If more than one demon are availible one is chosen from the
	 * $wgLaTeXMLUrl array.
	 * @return string
	 */
	private static function pickHost() {
		global $wgMathLaTeXMLUrl;
		if ( is_array( $wgMathLaTeXMLUrl ) ) {
			$host = array_rand( $wgMathLaTeXMLUrl );
		} else {
			$host = $wgMathLaTeXMLUrl;
		}
		wfDebugLog( "Math", "picking host " . $host );
		return $host;
	}

	/**
	 * Calculates the HTTP POST Data for the request. Depends on the settings
	 * and the input string only.
	 * @return string HTTP POST data
	 */
	public function getPostData() {
		$tex = $this->getTex();
		if ( is_null( $this->getDisplayStyle() ) ) {
			// default preserve the (broken) layout as it was
			$tex = '{\displaystyle ' . $tex . '}';
		}
		$texcmd = rawurlencode( $tex );
		$settings = $this->serializeSettings( $this->getLaTeXMLSettings() );
		$postData = $settings . '&tex=' . $texcmd;
		wfDebugLog( "Math", 'Get post data: ' . $postData );
		return $postData;
	}


	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return boolean
	 */
	protected function doRender() {
		global $wgMathDefaultLaTeXMLSetting, $wgMathDebug;
		if ( !$this->getTex() ) {
			wfDebugLog( "Math", "Rendering was requested, but no TeX string is specified." );
			$this->lastError = $this->getError( 'math_empty_tex' );
			return false;
		}
		$res = '';
		$host = self::pickHost();
		$post = $this->getPostData();
		$this->lastError = '';
		$requestResult = $this->makeRequest( $host, $post, $res, $this->lastError );
		if ( $requestResult ) {
			$result = json_decode( $res );
			if ( $result && json_last_error() === JSON_ERROR_NONE ) {
				if ( $this->isValidMathML( $result->result ) ) {
					$this->setMathml( $result->result );
					if ( $wgMathDebug ) {
						$this->setLog( $result->log );
						$this->setStatusCode( $result->status_code );
					}
					return true;
				} else {
					// Do not print bad mathml. It's probably too verbose and might
					// mess up the browser output.
					$this->lastError = $this->getError( 'math_invalidxml', $this->getModeStr(), $host );
					wfDebugLog( "Math", "\nLaTeXML InvalidMathML:"
							. var_export( array( 'post' => $post, 'host' => $host
								, 'result' => $res ), true ) . "\n\n" );
					return false;
				}
			} else {
				$this->lastError = $this->getError( 'math_invalidjson', $this->getModeStr(), $host );
				wfDebugLog( "Math", "\nLaTeXML InvalidJSON:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'res' => $res ), true ) . "\n\n" );
				return false;
			}
		} else {
			// Error message has already been set.
			return false;
		}
	}
	/**
	 * Sets the XML validaton.
	 * If set to false the output of LaTeXML is not validated.
	 * @param boolean $newval
	 */
	public function setXMLValidaton( $newval = true ) {
		$this->XMLValidation = $newval;
	}

}