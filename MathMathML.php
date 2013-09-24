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
class MathMathML extends MathRenderer {

	/**
	 * @var String settings for LaTeXML daemon
	 */
	private $LaTeXMLSettings = '';
	private static $DEFAULT_ALLOWED_ROOT_ELEMENTS = array( 'math', 'div', 'table', 'query' );
	private $allowedRootElements = '';

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
			//removes the [1] [2]... for the unnamed subarrays since LaTeXML
			//assigns multiple values to one key e.g.
			//preload=amsmath.sty&preload=amsthm.sty&preload=amstext.sty
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

	/* (non-PHPdoc)
	 * @see MathRenderer::render()
	 */

	public function render( $forceReRendering = false ) {
		global $wgMathFastDisplay;
		if ( $forceReRendering ) {
			$this->setPurge( true );
		}
		if ( $this->renderingRequired() ) {
			if ( $wgMathFastDisplay && !$this->isInDatabase() ) {
				$this->writeToDatabase();
				return true;
			}
			$res = $this->doRender();
			return $res;
			var_dump( $res );
		}
		return true;
	}

	/**
	 * Helper function to checks if the math tag must be rendered.
	 * @return boolean
	 */
	private function renderingRequired() {
		if ( $this->isPurge() ) {
			wfDebugLog( "Math", "Rerendering was requested." );
			return true;
		} else {
			$dbres = $this->readFromDatabase();
			if ( $dbres ) {
				if ( $this->isValidMathML( $this->getMathml() ) ) {
					wfDebugLog( "Math", "Valid MathML entry found in database." );
					if ( !$this->getSvg() ) {
						wfDebugLog( "Math", "No SVG rendering found in database." );
						return true;
					}
					if ( !$this->getPng() ) {
						wfDebugLog( "Math", "No PNG rendering found in database." );
						return true;
					}
					return false;
				} else {
					wfDebugLog( "Math", "Malformatted entry found in database" );
					return true;
				}
			} else {
				wfDebugLog( "Math", "No entry found in database." );
				return true;
			}
		}
	}

	/**
	 * Performs a HTTP Post request to the given host.
	 * Uses $wgLaTeXMLTimeout as timeout.
	 * Generates error messages on failure
	 * @see Http::post()
	 *
	 * @param string $host
	 * @param string $post the encoded post request
	 * @param mixed $res the result
	 * @param mixed $error the formatted error message or null
	 * @param String $httpRequestClass class name of MWHttpRequest (needed for testing only)
	 * @return boolean success
	 */
	public function makeRequest( $host, $post, &$res, &$error = '', $httpRequestClass = 'MWHttpRequest' ) {
		global $wgMathLaTeXMLTimeout;
		$error = '';
		$res = null;
		if ( $host == '' ) {
			$host = self::pickHost();
		}
		if ( $post ) {
			$this->getPostData();
		}
		$options = array( 'method' => 'POST', 'postData' => $post, 'timeout' => $wgMathLaTeXMLTimeout );
		$req = $httpRequestClass::factory( $host, $options );
		$status = $req->execute();
		if ( $status->isGood() ) {
			$res = $req->getContent();
			return true;
		} else {
			if ( $status->hasMessage( 'http-timed-out' ) ) {
				$error = $this->getError( 'math_latexml_timeout', $host );
				$res = false;
				wfDebugLog( "Math", "\nLaTeXML Timeout:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'wgLaTeXMLTimeout' => $wgMathLaTeXMLTimeout ), true ) . "\n\n" );
			} else {
				// for any other unkonwn http error
				$errormsg = $status->getHtml();
				$error = $this->getError( 'math_latexml_invalidresponse', $host, $errormsg );
				wfDebugLog( "Math", "\nLaTeXML NoResponse:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'errormsg' => $errormsg ), true ) . "\n\n" );
			}
			return false;
		}
	}

	/* (non-PHPdoc)
	 * @see MathRenderer::writeCache()
	 */

	public function writeCache() {
		if ( $this->isChanged() ) {
			$this->writeToDatabase();
		}
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
		if ( $this->getDisplaytyle() ) {
			$tex = '{\displaystyle ' . $tex . '}';
		}
		$texcmd = rawurlencode( $tex );
		$settings = $this->serializeSettings( $this->getLaTeXMLSettings() );
		$postData = $settings . '&tex=' . $texcmd;
		wfDebugLog( "Mat", 'Posting: ' . $postData );
		return $postData;
	}

	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return boolean
	 */
	private function doRender() {
		global $wgMathLaTeXMLRemote, $wgMathDefaultLaTeXMLSetting;
		if ( !$this->getTex() ) {
			$this->lastError = $this->getError( 'math_empty_tex' );
			return false;
		}
		$res = '';
		$host = self::pickHost();
		$this->setLaTeXMLSettings( $wgMathDefaultLaTeXMLSetting );
		$post = $this->getPostData();
		$this->lastError = '';
		if ( $wgMathLaTeXMLRemote ) {
			$requestResult = $this->makeRequest( $host, $post, $res, $this->lastError );
		} else {
			$requestResult = $this->makeShellRequest( '', $post, $res, $this->lastError );
		}
		if ( $requestResult ) {
			$result = json_decode( $res );
			if ( $result && json_last_error() === JSON_ERROR_NONE ) {
				if ( $this->isValidMathML( $result->result ) ) {
					$this->setMathml( $result->result );
					$this->setSvg( $result->svg );
					$this->setPng( $result->png );
					return true;
				} else {
					// Do not print bad mathml. It's probably too verbose and might
					// mess up the browser output.
					$this->lastError = $this->getError( 'math_latexml_invalidxml', $host );
					wfDebugLog( "Math", "\nLaTeXML InvalidMathML:"
							. var_export( array( 'post' => $post, 'host' => $host
								, 'result' => $result ), true ) . "\n\n" );
					return false;
				}
			} else {
				$this->lastError = $this->getError( 'math_latexml_invalidjson', $host );
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
	 * Checks if the input is valid MathML,
	 * and if the root element has the name math
	 * @param string $XML
	 * @return boolean
	 */
	public function isValidMathML( $XML ) {
		$out = false;
		// depends on https://gerrit.wikimedia.org/r/#/c/66365/
		if ( !is_callable( 'XmlTypeCheck::newFromString' ) ) {
			$msg = wfMessage( 'math_latexml_xmlversion' )->inContentLanguage()->escaped();
			trigger_error( $msg, E_USER_NOTICE );
			wfDebugLog( 'Math', $msg );
			return true;
		}
		$xmlObject = new XmlTypeCheck( $XML, null, false );
		if ( !$xmlObject->wellFormed ) {
			wfDebugLog( "Math", "XML validation error:\n " . var_export( $XML, true ) . "\n" );
		} else {
			$name = $xmlObject->getRootElement();
			$elementSplit = explode( ':', $name );
			if ( in_array( end( $elementSplit ), $this->getAllowedRootElements() ) ) {
				$out = true;
			} else {
				wfDebugLog( "Math", 'got wrong root element :' . end( $elementSplit ) . ' with namespace ' . $name );
			}
		}
		return $out;
	}

	/**
	 *
	 * @return DOMDocument
	 */
	public function getDom() {
		$dom = new DOMDocument;
		return $dom->loadXML( $this->getMathml() );
	}
	/**
	 *
	 * @param type $png
	 * @return type
	 */
	private function getFallbackImageUrl( $png = false ) {
		return SpecialPage::getTitleFor( 'MathShowImage' )->getLocalURL( array(
					'hash' => $this->getMd5(),
					'png' => $png )
		);
	}

	/**
	 * Gets img tag for math image
	 *
	 * @return string img HTML
	 */
	public function getFallbackImage( $png = false ) {
		$url = $this->getFallbackImageUrl( $png );
		$class = $this->getClassName( true, $png );
		return Xml::element( 'img', $this->getAttributes(
								'img', array(
							'class' => $class
//					,'alt' => $this->getTex()
								), array(
							'src' => $url
								)
						)
		);
	}

	/**
	 *
	 * @param boolean $fallback
	 * @param boolean $png
	 * @return string
	 */
	private function getClassName( $fallback = false, $png = false ) {
		$class = "mwe-math-";
		if ( $fallback ) {
			$class .= 'fallback-';
			if ( $png ) {
				$class .= 'png-';
			} else {
				$class .= 'svg-';
			}
		} else {
			$class .= 'mathml-';
		}
		if ( $this->getDisplaytyle() ) {
			$class .= 'display';
		} else {
			$class .= 'inline';
		}
		return $class;
	}

	/**
	 * Internal version of @link self::embedMathML
	 * @return string
	 * @return html element with rendered math
	 */
	public function getHtmlOutput() {
		global $wgMathDebug;
		$output = $wgMathDebug ? "\n<!--Math Block Beginn-->\n" : '';
		$output .= $this->getFallbackImage( false ) . "\n";
		$output .= $this->getFallbackImage( true ) . "\n";
		$class = $this->getClassName();
		//MathML has to be wrapped into a div or span in order to be able to hide it.
		$element = $this->getDisplaytyle() ? 'div' : 'span';

		//$output .= Html::rawElement( $element, array( 'class' => $class ) , $this->getMathML());
		$output .= Xml::tags( 'span', array( 'class' => $class ), $this->getMathML() );
		$output .= $wgMathDebug ? "\n<!--Math Block End-->\n" : '';
		return $output;
	}


	/**
	 *
	 * @param type $host
	 * @param type $post
	 * @param type $res
	 * @param type $error
	 * @return boolean
	 */
	private function makeShellRequest( $host, $post, &$res, &$error = '' ) {
		$cmd = 'latexmlmediawiki' . ' ' . wfEscapeShellArg( $this->getPostData() );
		$retval = false;
		$res = wfShellExec( $cmd, $retval );
		if ( $retval !== 0 ) {
			return false;
		} else {
			return true;
		}
	}

}