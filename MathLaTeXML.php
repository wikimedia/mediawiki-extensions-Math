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

class MathLaTeXML extends MathRenderer {

	/**
	 * @var String settings for LaTeXML daemon
	 */
	private $LaTeXMLSettings = '';
	/** @var boolean if false LaTeXML output is not validated */
	private $XMLValidation = true;
	protected static $DEFAULT_ALLOWED_ROOT_ELEMENTS = array( 'math', 'div', 'table', 'query' );
	protected $allowedRootElements = '';

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
	 * @global (array|string) $wgMathDefaultLaTeXMLSetting
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
		wfProfileIn( __METHOD__ );
		if ( $forceReRendering ) {
			$this->setPurge( true );
		}
		if ( $this->renderingRequired() ) {
			$res = $this->doRender( );
			if ( ! $res ) {
				wfProfileOut( __METHOD__ );
				return $this->getLastError();
			}
		}
		$result = $this->getMathMLTag();
		wfProfileOut( __METHOD__ );
		return $result;
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
					wfDebugLog( "Math", "Valid entry found in database." );
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
	 * Uses $wgMathLaTeXMLTimeout as timeout.
	 * Generates error messages on failure
	 * @see Http::post()
	 *
	 * @global int $wgMathLaTeXMLTimeout
	 * @param string $host
	 * @param string $post the encoded post request
	 * @param mixed $res the result
	 * @param mixed $error the formatted error message or null
	 * @param String $httpRequestClass class name of MWHttpRequest (needed for testing only)
	 * @return boolean success
	 */
	public function makeRequest( $host, $post, &$res, &$error = '', $httpRequestClass = 'MWHttpRequest' ) {
		global $wgMathLaTeXMLTimeout;

		wfProfileIn( __METHOD__ );
		$error = '';
		$res = null;
		$options = array( 'method' => 'POST', 'postData' => $post, 'timeout' => $wgMathLaTeXMLTimeout );
		/** @var $req (CurlHttpRequest|PhpHttpRequest) the request object  */
		$req = $httpRequestClass::factory( $host, $options );
		$status = $req->execute();
		if ( $status->isGood() ) {
			$res = $req->getContent();
			wfProfileOut( __METHOD__ );
			return true;
		} else {
			if ( $status->hasMessage( 'http-timed-out' ) ) {
				$error = $this->getError( 'math_latexml_timeout', $host );
				$res = false;
				wfDebugLog( "Math", "\nLaTeXML Timeout:"
					. var_export( array( 'post' => $post, 'host' => $host
						, 'timeout' => $wgMathLaTeXMLTimeout ), true ) . "\n\n" );
			} else {
				// for any other unkonwn http error
				$errormsg = $status->getHtml();
				$error = $this->getError( 'math_latexml_invalidresponse', $host, $errormsg );
				wfDebugLog( "Math", "\nLaTeXML NoResponse:"
					. var_export( array( 'post' => $post, 'host' => $host
						, 'errormsg' => $errormsg ), true ) . "\n\n" );
			}
			wfProfileOut( __METHOD__ );
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
	 * $wgMathLaTeXMLUrl array.
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
		if ( $this->getMathStyle() == MW_MATHSTYLE_INLINE_DISPLAYSTYLE ) {
			// In MW_MATHSTYLE_INLINE_DISPLAYSTYLE the old
			// texvc behavior is reproduced:
			// The equation is rendered in displaystyle
			// (texvc used $$ $tex $$ to render)
			// but the equation is not centered.
			$tex = '{\displaystyle ' . $tex . '}';
		}
		$texcmd = rawurlencode( $tex );
		$settings = $this->serializeSettings( $this->getLaTeXMLSettings( ) );
		return  $settings. '&tex=' . $texcmd;
	}
	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return boolean
	 */
	private function doRender( ) {
		wfProfileIn( __METHOD__ );
		$host = self::pickHost();
		$post = $this->getPostData();
		$this->lastError = '';
		if ( $this->makeRequest( $host, $post, $res, $this->lastError ) ) {
			$result = json_decode( $res );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( $this->isValidMathML( $result->result ) ) {
					$this->setMathml( $result->result );
					wfProfileOut( __METHOD__ );
					return true;
				} else {
					// Do not print bad mathml. It's probably too verbose and might
					// mess up the browser output.
					$this->lastError = $this->getError( 'math_latexml_invalidxml', $host );
					wfDebugLog( "Math", "\nLaTeXML InvalidMathML:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'result' => $result ), true ) . "\n\n" );
					wfProfileOut( __METHOD__ );
					return false;
				}
			} else {
					$this->lastError = $this->getError( 'math_latexml_invalidjson', $host );
					wfDebugLog( "Math", "\nLaTeXML InvalidJSON:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'res' => $res ), true ) . "\n\n" );
					wfProfileOut( __METHOD__ );
					return false;
				}
		} else {
			// Error message has already been set.
			wfProfileOut( __METHOD__ );
			return false;
		}
	}

	/**
	 * Sets the XML validation.
	 * If set to false the output of LaTeXML is not validated.
	 * @param boolean $validation
	 */
	public function setXMLValidation( $validation = true ) {
		$this->XMLValidation = $validation;
	}

	/**
	 * Checks if the input is valid MathML,
	 * and if the root element has the name math
	 * @param string $XML
	 * @return boolean
	 */
	public function isValidMathML( $XML ) {
		$out = false;
		if ( !$this->XMLValidation ) {
			return true;
		}
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
			if ( is_array($elementSplit) ){
				$localName = end( $elementSplit );
			} else {
				$localName = $name;
			}
			if ( in_array( $localName , $this->getAllowedRootElements() ) ) {
				$out = true;
			} else {
				wfDebugLog( "Math", "got wrong root element : $name" );
			}
		}
		return $out;
	}

	/**
	 * Internal version of @link self::embedMathML
	 * @return string
	 * @return html element with rendered math
	 */
	private function getMathMLTag() {
		return self::embedMathML( $this->getMathml(), urldecode( $this->getTex() ) );
	}

	/**
	 * Embeds the MathML-XML element in a HTML span element with class tex
	 * @param string $mml : the MathML string
	 * @param string $tagId : optional tagID for references like (pagename#equation2)
	 * @param bool $attribs
	 * @return html element with rendered math
	 */
	public static function embedMathML( $mml, $tagId = '', $attribs = false ) {
		$mml = str_replace( "\n", " ", $mml );
		if ( ! $attribs ) {
			$attribs = array( 'class' => 'tex', 'dir' => 'ltr' );
			if ( $tagId ) {
				$attribs['id'] = $tagId;
			}
			$attribs = Sanitizer::validateTagAttributes( $attribs, 'span' );
		}
		return Xml::tags( 'span', $attribs, $mml );
	}

}