<?php
/**
 * MediaWiki math extension
 *
 * (c)2012 Moritz Schubotz
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the MathML daemon
 * @file
 */
class MathMathML extends MathRenderer {

	protected $defaultAllowedRootElements = array( 'math' );
	protected $allowedRootElements = '';
	protected $hosts;
	/** @var boolean if false MathML output is not validated */
	private $XMLValidation = true;

	/**
	 * Gets the allowed root elements the rendered math tag might have.
	 *
	 * @return array
	 */
	public function getAllowedRootElements() {
		if ( $this->allowedRootElements ) {
			return $this->allowedRootElements;
		} else {
			return $this->defaultAllowedRootElements;
		}
	}

	/**
	 * Sets the XML validation.
	 * If set to false the output of MathML is not validated.
	 * @param boolean $validation
	 */
	public function setXMLValidation( $validation = true ) {
		$this->XMLValidation = $validation;
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
			wfProfileOut( __METHOD__ );
			return $this->doRender( );
		}
		wfProfileOut( __METHOD__ );
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
		// TODO: Change the timeout mechanism.
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
				$error = $this->getError( 'math_timeout', $this->getModeStr(), $host );
				$res = false;
				wfDebugLog( "Math", "\nTimeout:"
					. var_export( array( 'post' => $post, 'host' => $host
					, 'timeout' => $wgMathLaTeXMLTimeout ), true ) . "\n\n" );
			} else {
				// for any other unkonwn http error
				$errormsg = $status->getHtml();
				$error = $this->getError( 'math_invalidresponse', $this->getModeStr(), $host, $errormsg, $this->getModeStr( MW_MATH_MATHML ) );
				wfDebugLog( "Math", "\nNoResponse:"
					. var_export( array( 'post' => $post, 'host' => $host
					, 'errormsg' => $errormsg ), true ) . "\n\n" );
			}
			wfProfileOut( __METHOD__ );
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
		if ( !$this->XMLValidation ) {
			return true;
		}
		// depends on https://gerrit.wikimedia.org/r/#/c/66365/
		if ( !is_callable( 'XmlTypeCheck::newFromString' ) ) {
			$msg = wfMessage( 'math_xmlversion' )->inContentLanguage()->escaped();
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

	/* (non-PHPdoc)
	 * @see MathRenderer::writeCache()
	*/
	public function writeCache() {
		if ( $this->isChanged() ) {
			$this->writeToDatabase();
		}
	}

	/**
	 * Picks a daemon.
	 * If more than one demon are available one is chosen from the
	 * hosts array.
	 * @return string
	 */
	protected function pickHost() {
		if ( is_array( $this->hosts ) ) {
			$host = array_rand( $this->hosts );
		} else {
			$host = $this->hosts;
		}
		wfDebugLog( "Math", "picking host " . $host );
		return $host;
	}


	protected function getMathTableName() {
		return 'mathoid';
	}
	/**
	 * Calculates the default class name for a math element
	 * @param boolean $fallback
	 * @param boolean $png
	 * @return string the class name
	 */
	protected function getClassName( $fallback = false, $png = false ) {
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
		if ( $this->getMathStyle() == MW_MATHSTYLE_DISPLAY ) {
			$class .= 'display';
		} else {
			$class .= 'inline';
		}
		return $class;
	}
	/**
	 * @return string Html output that is embedded in the page
	 */
	public function getHtmlOutput() {
		if ( $this->getMathStyle() == MW_MATHSTYLE_DISPLAY  ) {
			$element = 'div';
		} else {
			$element = 'span';
		}
		$attribs = array();
		$output = HTML::openElement( $element , $attribs );
		// MathML has to be wrapped into a div or span in order to be able to hide it.
		if ( $this->getMathStyle() == MW_MATHSTYLE_DISPLAY ) {
			// Remove displayStyle attributes set by the MathML converter
			$mml = preg_replace( '/(display|mode)=["\'](inline|block)["\']/', '', $this->getMathml() );
			// and insert the correct value
			$mml = preg_replace( '/<math/', '<math display="block"', $mml );
		} else {
			$mml = $this->getMathml();
		}
		$output .= Xml::tags( $element, array( 'class' => $this->getClassName(),
				'style' => 'display: none;' ),
			$mml );
		$output .= HTML::closeElement( $element );
		return $output;
	}
}