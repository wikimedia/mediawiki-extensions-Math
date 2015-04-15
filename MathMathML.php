<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2015 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

use MediaWiki\Logger\LoggerFactory;

/**
 * Converts LaTeX to MathML using the mathoid-server
 */
class MathMathML extends MathRenderer {

	protected $defaultAllowedRootElements = array( 'math' );
	protected $allowedRootElements = '';
	protected $hosts;

	/** @var boolean if false MathML output is not validated */
	private $XMLValidation = true;
	protected $inputType = 'tex';

	/**
	 * @param string $inputType
	 */
	public function setInputType($inputType) {
		$this->inputType = $inputType;
	}

	/**
	 * @return string
	 */
	public function getInputType() {
		return $this->inputType;
	}

	public function __construct( $tex = '', $params = array() ) {
		global $wgMathMathMLUrl;
		parent::__construct( $tex, $params );
		$this->setMode( MW_MATH_MATHML );
		$this->hosts = $wgMathMathMLUrl;
		if ( isset( $params['type'] ) ) {
			if ( $params['type'] == 'pmml' ) {
				$this->inputType = 'pmml';
				$this->setMathml( '<math>' . $tex . '</math>' );
			} elseif ( $params['type'] == 'ascii' ) {
				$this->inputType = 'ascii';
			}
		}
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
	public function setAllowedRootElements( $settings ) {
		$this->allowedRootElements = $settings;
	}

	/* (non-PHPdoc)
	 * @see MathRenderer::render()
	*/
	public function render( $forceReRendering = false ) {
		if ( $forceReRendering ) {
			$this->setPurge( true );
		}
		if ( $this->renderingRequired() ) {
			return $this->doRender();
		}
		return true;
	}

	/**
	 * Helper function to checks if the math tag must be rendered.
	 * @return boolean
	 */
	private function renderingRequired() {
		$logger = LoggerFactory::getInstance( 'Math' );
		if ( $this->isPurge() ) {
			$logger->debug( 'Rerendering was requested.' );
			return true;
		} else {
			$dbres = $this->isInDatabase();
			if ( $dbres ) {
				if ( $this->isValidMathML( $this->getMathml() ) ) {
					$logger->debug( 'Valid MathML entry found in database.' );
					if ( $this->getSvg( 'cached' ) ) {
						$logger->debug( 'SVG-fallback found in database.' );
						return false;
					} else {
						$logger->debug( 'SVG-fallback missing.' );
						return true;
					}
				} else {
					$logger->debug( 'Malformatted entry found in database' );
					return true;
				}
			} else {
				$logger->debug( 'No entry found in database.' );
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

		$error = '';
		$res = null;
		if ( !$host ) {
			$host = self::pickHost();
		}
		if ( !$post ) {
			$this->getPostData();
		}
		$options = array( 'method' => 'POST', 'postData' => $post, 'timeout' => $wgMathLaTeXMLTimeout );
		/** @var $req (CurlHttpRequest|PhpHttpRequest) the request object  */
		$req = $httpRequestClass::factory( $host, $options );
		/** @var Status $req Status the request status */
		$status = $req->execute();
		if ( $status->isGood() ) {
			$res = $req->getContent();
			return true;
		} else {
			if ( $status->hasMessage( 'http-timed-out' ) ) {
				$error = $this->getError( 'math_timeout', $this->getModeStr(), $host );
				$res = false;
				LoggerFactory::getInstance( 'Math' )->warning( 'Timeout:' . var_export( array(
						'post' => $post,
						'host' => $host,
						'timeout' => $wgMathLaTeXMLTimeout
					), true ) );
			} else {
				// for any other unkonwn http error
				$errormsg = $status->getHtml();
				$error =
					$this->getError( 'math_invalidresponse', $this->getModeStr(), $host, $errormsg,
						$this->getModeStr( MW_MATH_MATHML ) );
				LoggerFactory::getInstance( 'Math' )->warning( 'NoResponse:' . var_export( array(
						'post' => $post,
						'host' => $host,
						'errormsg' => $errormsg
					), true ) );
			}
			return false;
		}
	}

	/**
	 * Return a MathML daemon host.
	 *
	 * If more than one demon is available, one is chosen at random.
	 *
	 * @return string
	 */
	protected function pickHost() {
		if ( is_array( $this->hosts ) ) {
			$host = array_rand( $this->hosts );
		} else {
			$host = $this->hosts;
		}
		LoggerFactory::getInstance( 'Math' )->debug( 'Picking host ' . $host );
		return $host;
	}

	/**
	 * Calculates the HTTP POST Data for the request. Depends on the settings
	 * and the input string only.
	 * @return string HTTP POST data
	 */
	public function getPostData() {
		$input = $this->getTex();
		if ( $this->inputType == 'pmml' ||
			 $this->getMode() == MW_MATH_LATEXML && $this->getMathml() ) {
			$out = 'type=mml&q=' . rawurlencode( $this->getMathml() );
		} elseif ( $this->inputType == 'ascii' ) {
			$out = 'type=asciimath&q=' . rawurlencode( $input );
		} else {
			if ( $this->getMathStyle() == MW_MATHSTYLE_INLINE_DISPLAYSTYLE ) {
				// default preserve the (broken) layout as it was
				$out = 'type=inline-TeX&q=' . rawurlencode( '{\\displaystyle ' . $input . '}' );
			} else {
				$out = 'type=tex&q=' . rawurlencode( $input );
			}
		}
		LoggerFactory::getInstance( 'Math' )->debug( 'Get post data: ' . $out );
		return $out;
	}

	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return boolean
	 */
	protected function doRender() {
		if ( $this->getTex() === '' ) {
			LoggerFactory::getInstance( 'Math' )->debug( 'Rendering was requested, but no TeX string is specified.' );
			$this->lastError = $this->getError( 'math_empty_tex' );
			return false;
		}
		$res = '';
		$host = self::pickHost();
		$post = $this->getPostData();
		$this->lastError = '';
		$requestResult = $this->makeRequest( $host, $post, $res, $this->lastError );
		if ( $requestResult ) {
			$jsonResult = json_decode( $res );
			if ( $jsonResult && json_last_error() === JSON_ERROR_NONE ) {
				if ( $jsonResult->success ) {
					return $this->processJsonResult( $jsonResult, $host );
				} else {
					if ( property_exists( $jsonResult, 'log' ) ) {
						$log = $jsonResult->log;
					} else {
						$log = wfMessage( 'math_unknown_error' )->inContentLanguage()->escaped();
					}
					$this->lastError = $this->getError( 'math_mathoid_error', $host, $log );
					LoggerFactory::getInstance( 'Math' )->warning(
						'Mathoid conversion error:' . var_export( array(
							'post' => $post,
							'host' => $host,
							'result' => $res
						), true ) );
					return false;
				}
			} else {
				$this->lastError = $this->getError( 'math_invalidjson', $host );
				LoggerFactory::getInstance( 'Math' )->error(
					'MathML InvalidJSON:' . var_export( array(
						'post' => $post,
						'host' => $host,
						'res' => $res
					), true ) );
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
		if ( !$this->XMLValidation ) {
			return true;
		}

		$xmlObject = new XmlTypeCheck( $XML, null, false );
		if ( !$xmlObject->wellFormed ) {
			LoggerFactory::getInstance( 'Math' )->error(
				'XML validation error: ' . var_export( $XML, true ) );
		} else {
			$name = $xmlObject->getRootElement();
			$elementSplit = explode( ':', $name );
			if ( is_array($elementSplit) ) {
				$localName = end( $elementSplit );
			} else {
				$localName = $name;
			}
			if ( in_array( $localName , $this->getAllowedRootElements() ) ) {
				$out = true;
			} else {
				LoggerFactory::getInstance( 'Math' )->error( "Got wrong root element : $name" );
			}
		}
		return $out;
	}

	/**
	 * @param boolean $noRender
	 * @return type
	 */
	private function getFallbackImageUrl( $noRender = false ) {
		return SpecialPage::getTitleFor( 'MathShowImage' )->getLocalURL( array(
				'hash' => $this->getMd5(),
				'mode' => $this->getMode(),
				'noRender' => $noRender )
		);
	}

	/**
	 * Helper function to correct the style information for a
	 * linked SVG image.
	 * @param string $svg SVG-image data
	 * @param string $style current style information to be updated
	 */
	public function correctSvgStyle( $svg, &$style ) {
		if ( preg_match( '/style="([^"]*)"/', $svg, $styles ) ) {
			$style .= ' ' . $styles[1]; // merge styles
			if ( $this->getMathStyle() === MW_MATHSTYLE_DISPLAY ) {
				// TODO: Improve style cleaning
				$style = preg_replace( '/margin\-(left|right)\:\s*\d+(\%|in|cm|mm|em|ex|pt|pc|px)\;/', '', $style );
			}
			$style = preg_replace( '/position:\s*absolute;\s*left:\s*0px;/', '', $style );
		}
		// TODO: Figure out if there is a way to construct
		// a SVGReader from a string that represents the SVG
		// content
		if ( preg_match( "/height=\"(.*?)\"/" , $this->getSvg(), $matches ) ) {
			$style .= 'height: ' . $matches[1] . '; ';
		}
		if ( preg_match( "/width=\"(.*?)\"/", $this->getSvg(), $matches ) ) {
			$style .= 'width: ' . $matches[1] . ';';
		}
	}

	/**
	 * Gets img tag for math image
	 * @param boolean $noRender if true no rendering will be performed if the image is not stored in the database
	 * @param boolean|string $classOverride if classOverride is false the class name will be calculated by getClassName
	 * @return string XML the image html tag
	 */
	private function getFallbackImage( $noRender = false, $classOverride = false ) {
		$url = $this->getFallbackImageUrl( $noRender );

		$attribs = array();
		if ( $classOverride === false ) { // $class = '' suppresses class attribute
			$class = $this->getClassName( true );
		} else {
			$class  = $classOverride;
		}

		// TODO: move the common styles to the global stylesheet!
		$style = 'background-image: url(\''. $url .
				 '\'); background-repeat: no-repeat; background-size: 100% 100%;';
		$this->correctSvgStyle( $this->getSvg(), $style );
		if ( $class ) { $attribs['class'] = $class; }
		if ( $style ) { $attribs['style'] = $style; }
		// Don't use an empty span, as that is going to be stripped by HTML tidy
		// when enabled (which is true in production).
		return Xml::element( 'meta', $this->getAttributes( 'span', $attribs , array( 'aria-hidden' => 'true' ) ) );
	}


	protected function getMathTableName() {
		return 'mathoid';
	}
	/**
	 * Calculates the default class name for a math element
	 * @param boolean $fallback
	 * @return string the class name
	 */
	private function getClassName( $fallback = false ) {
		$class = 'mwe-math-';
		if ( $fallback ) {
			$class .= 'fallback-image-';
		} else {
			$class .= 'mathml-';
		}
		if ( $this->getMathStyle() == MW_MATHSTYLE_DISPLAY ) {
			$class .= 'display';
		} else {
			$class .= 'inline';
		}
		if ( !$fallback) {
			$class .= ' mwe-math-mathml-a11y';
		}
		return $class;
	}
	/**
	 * @return string Html output that is embedded in the page
	 */
	public function getHtmlOutput() {
		if ( $this->getMathStyle() == MW_MATHSTYLE_DISPLAY ) {
			$element = 'div';
		} else {
			$element = 'span';
		}
		$attribs = array();
		if ( $this->getID() !== '' ) {
			$attribs['id'] = $this->getID();
		}
		$output = HTML::openElement( $element, $attribs );
		// MathML has to be wrapped into a div or span in order to be able to hide it.
		// Remove displayStyle attributes set by the MathML converter
		// (Beginning from Mathoid 0.2.5 block is the default layout.)
		$mml = preg_replace( '/(<math[^>]*)(display|mode)=["\'](inline|block)["\']/', '$1', $this->getMathml() );
		if ( $this->getMathStyle() == MW_MATHSTYLE_DISPLAY ) {
			$mml = preg_replace( '/<math/', '<math display="block"', $mml );
		}
		$output .= Xml::tags( $element, array( 'class' => $this->getClassName(), 'style' => 'display: none;'  ), $mml );
		$output .= $this->getFallbackImage( );
		$output .= HTML::closeElement( $element );
		return $output;
	}

	protected function dbOutArray() {
		$out = parent::dbOutArray();
		if ($this->getMathTableName() == 'mathoid' ) {
			$out['math_input'] = $out['math_inputtex'];
			unset($out['math_inputtex']);
		}
		return $out;
	}

	protected function dbInArray() {
		$out = parent::dbInArray();
		if ($this->getMathTableName() == 'mathoid' ) {
			$out = array_diff( $out, array( 'math_inputtex' ) );
			$out[] = 'math_input';
		}
		return $out;
	}

	protected function initializeFromDatabaseRow( $rpage ) {
		// mathoid allows different input formats
		// therefore the column name math_inputtex was changed to math_input
		if ( $this->getMathTableName() == 'mathoid' && ! empty( $rpage->math_input ) ) {
			$this->userInputTex = $rpage->math_input;
		}
		parent::initializeFromDatabaseRow( $rpage );

	}

	/**
	 * @param $jsonResult
	 * @param $host
	 *
	 * @return bool
	 */
	private function processJsonResult( $jsonResult, $host ) {
		global $wgMathDebug;
		if ( $this->getMode() == MW_MATH_LATEXML || $this->inputType == 'pmml' ||
			 $this->isValidMathML( $jsonResult->mml )
		) {
			if ( isset( $jsonResult->svg ) ) {
				$xmlObject = new XmlTypeCheck( $jsonResult->svg, null, false );
				if ( !$xmlObject->wellFormed ) {
					$this->lastError = $this->getError( 'math_invalidxml', $host );
					return false;
				} else {
					$this->setSvg( $jsonResult->svg );
				}
			} else {
				LoggerFactory::getInstance( 'Math' )->error(
					'Missing SVG property in JSON result.' );
			}
			if ( $wgMathDebug ) {
				$this->setLog( $jsonResult->log );
			}
			if ( $this->getMode() != MW_MATH_LATEXML && $this->inputType != 'pmml' ) {
				$this->setMathml( $jsonResult->mml );
			}
			return true;
		} else {
			$this->lastError = $this->getError( 'math_unknown_error', $host );
			return false;
		}
	}
}
