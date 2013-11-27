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

	private static $DEFAULT_ALLOWED_ROOT_ELEMENTS = array( 'math', 'div', 'table', 'query' );
	private $allowedRootElements = '';

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
			wfDebugLog( "Math", "Rendering was requested." );
			$res = $this->doRender();
			return $res;
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
					if ( $this->getSvg()  ) {
						wfDebugLog( "Math", "SVG-fallback found in database." );
						return false;
					} else {
						wfDebugLog( "Math", "SVG-fallback missing." );
						return true;
					}
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
	 * Uses $wgMathMLTimeout as timeout.
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
		global $wgMathMathMLTimeout;
		$error = '';
		$res = null;
		if ( !$host ) {
			$host = self::pickHost();
		}
		if ( !$post ) {
			$this->getPostData();
		}
		$options = array( 'method' => 'POST', 'postData' => $post, 'timeout' => $wgMathMathMLTimeout );
		$req = $httpRequestClass::factory( $host, $options );
		$status = $req->execute();
		if ( $status->isGood() ) {
			$res = $req->getContent();
			return true;
		} else {
			if ( $status->hasMessage( 'http-timed-out' ) ) {
				$error = $this->getError( 'math_latexml_timeout', $host );
				$res = false;
				wfDebugLog( "Math", "\nMathML Timeout:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'wgMathMLTimeout' => $wgMathMathMLTimeout ), true ) . "\n\n" );
			} else {
				// for any other unkonwn http error
				$errormsg = $status->getHtml();
				$error = $this->getError( 'math_latexml_invalidresponse', $host, $errormsg );
				wfDebugLog( "Math", "\nMathML NoResponse:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'errormsg' => $errormsg ), true ) . "\n\n" );
			}
			return false;
		}
	}


	/**
	 * Picks a MathML daemon.
	 * If more than one demon are availible one is chosen from the
	 * $wgMathMLUrl array.
	 * @return string
	 */
	private static function pickHost() {
		global $wgMathMathMLUrl;
		if ( is_array( $wgMathMathMLUrl ) ) {
			$host = array_rand( $wgMathMathMLUrl );
		} else {
			$host = $wgMathMathMLUrl;
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
		if ( is_null( $this->getDisplaytyle() ) ){
			//default preserve the (broken) layout as it was
			$tex= '{\displaystyle '. $tex.'}';
		}
		wfDebugLog( "Math", 'Get post data: ' . $tex );
		return 'tex='.rawurlencode( $tex );
	}

	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return boolean
	 */
	private function doRender() {
		global  $wgMathDebug;
		if ( $this->getTex() ==='' ) {
			wfDebugLog( "Math", "Rendering was requested, but no TeX string is specified.");
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
				if ( $result->sucess ){
					if ( $this->isValidMathML( $result->mml ) ) {
						$this->setMathml( $result->mml );
						$this->setSvg( $result->svg );
						if ( $wgMathDebug ) {
							$this->setLog( $result->log );
						}
						return true;
					} else {
						$this->lastError = $this->getError( 'math_unknown_error', $host );
					}
				} else {
					// Do not print bad mathml. It's probably too verbose and might
					// mess up the browser output.
					$this->lastError = $this->getError( 'math_latexml_invalidxml', $host );
					wfDebugLog( "Math", "\nMathML InvalidMathML:"
							. var_export( array( 'post' => $post, 'host' => $host
								, 'result' => $res ), true ) . "\n\n" );
					return false;
				}
			} else {
				$this->lastError = $this->getError( 'math_latexml_invalidjson', $host );
				wfDebugLog( "Math", "\nMathML InvalidJSON:"
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
	 * @param boolean $noRender
	 * @return type
	 */
	private function getFallbackImageUrl( $png = false, $noRender = false ) {
		return SpecialPage::getTitleFor( 'MathShowImage' )->getLocalURL( array(
					'hash' => $this->getMd5(),
					'png' => $png,
					'noRender' => $noRender)
		);
	}

	/**
	 * Gets img tag for math image
	 * @param boolean $noRender if true no rendering will be performed if the image is not stored in the database
	 * @param string|false $classOverride if classOverride is false the class name will be calcuated by getClassName
	 * @return string XML the image html tag
	 */
	public function getFallbackImage( $png = false, $noRender = false, $classOverride = false ) {
		$url = $this->getFallbackImageUrl( $png , $noRender);
		$style = '';
		$attribs = array();
		if( $classOverride === false ){ //$class ='' osuppresses class attribute
			$class = $this->getClassName( true, $png );
			$style = $png ? 'display: none;' : '';
		} else {
			$class  = $classOverride;
			$style = '';
		}
		if ( !$png){
			$svg = $this->getSvg();
			if( preg_match('/style="([^"]*)"/', $svg, $styles) ){
				$style = $styles[1];
				if ( $this->getDisplaytyle()===true){
					$style = preg_replace('/margin\-(left|right)\:\s*\d+(\%|in|cm|mm|em|ex|pt|pc|px)\;/', '', $style);
					$style .= 'display:block;  margin-left: auto;  margin-right: auto;';
				}
			}
		}
		if ( $class ) { $attribs['class'] = $class; }
		if ( $style ) { $attribs['style'] = $style; }
		//an alteranative for svg might be an object with type="image/svg+xml"
		return Xml::element( 'img', $this->getAttributes( 'img', $attribs , array( 'src' => $url )) );
	}

	/**
	 * Calculates the default class name for a math element
	 * @param boolean $fallback
	 * @param boolean $png
	 * @return string the class name
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
		if ( $this->getDisplaytyle() ){
			$element = 'div';
		} else {
			$element = 'span';
		}
		$attribs = array();
		if ( $this->getID() !=='' ){
			$attribs['id'] = $this->getID();
		}
		$output = HTML::openElement($element , $attribs);
		//MathML has to be wrapped into a div or span in order to be able to hide it.
		if ( $this->getDisplaytyle() == true ){
			//Remove displaystyle attributes set by the MathML converter
			$mml = preg_replace('/(display|mode)=["\'](inline|block)["\']/', '', $this->getMathml());
			//and isert the corrent value
			$mml = preg_replace('/<math/', '<math display="block"', $mml);
		} else {
			$mml = $this->getMathml();
		}
		$output .= Xml::tags( $element, array( 'class' => $this->getClassName(),
			'style'=>'display: none;' ),
			$mml );
		$output .= $this->getFallbackImage( false ) . "\n";
		$output .= $this->getFallbackImage( true ) . "\n";
		$output .= HTML::closeElement($element);
		return $output;
	}
}