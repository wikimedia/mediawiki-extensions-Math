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
	public function setInputType( $inputType ) {
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
		$this->setMode( 'mathml' );
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
		return $this->doRender();
	}


	/**
	 * Performs a HTTP Post request to the given host.
	 * Uses $wgMathLaTeXMLTimeout as timeout.
	 * Generates error messages on failure
	 * @see Http::post()
	 *
	 * @param string $host
	 * @param string $post the encoded post request
	 * @param mixed $res the result
	 * @param mixed $error the formatted error message or null
	 * @param String $httpRequestClass class name of MWHttpRequest (needed for testing only)
	 * @param String $hash the hash code to retrieve the SVG-image
	 * @global int $wgMathLaTeXMLTimeout
	 * @return bool success
	 */
	public function makeRequest(
			$host, $post, &$res, &$error = '', $httpRequestClass = 'MWHttpRequest', &$hash = null
		) {
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
		/** @var MWHttpRequest $req the request object  */
		$req = $httpRequestClass::factory( $host, $options );
		/** @var Status $status Status the request status */
		$status = $req->execute();
		if ( $status->isGood() ) {
			$res = $req->getContent();
			$headers = $req->getResponseHeaders();
			$hash = $headers['x-resource-location'];
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
						$this->getModeStr( 'mathml' ) );
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
			$this->hosts = $host; // Use the same host for this class instance
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
			 $this->getMode() == 'latexml' && $this->getMathml() ) {
			$out = 'type=mml&q=' . rawurlencode( $this->getMathml() );
		} elseif ( $this->inputType == 'ascii' ) {
			$out = 'type=asciimath&q=' . rawurlencode( $input );
		} else {
			if ( $this->getMathStyle() == 'inlineDisplaystyle' ) {
				// default preserve the (broken) layout as it was
				$out = 'type=inline-TeX&q=' . rawurlencode( '{\\displaystyle ' . $input . '}' );
			} elseif ( $this->getMathStyle() == 'inline' ) {
				$out = 'type=inline-TeX&q=' . rawurlencode( $input );
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
			LoggerFactory::getInstance( 'Math' )->debug(
				'Rendering was requested, but no TeX string is specified.'
			);
			$this->lastError = $this->getError( 'math_empty_tex' );
			return false;
		}
		$res = '';
		$host = self::pickHost();
		$post = $this->getPostData();
		$this->lastError = '';
		$hash = '';
		$requestResult = $this->makeRequest( $host, $post, $res, $this->lastError, 'MWHttpRequest', $hash );
		if ( $requestResult ) {
			//@ TODO implement handling here.
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
			if ( is_array( $elementSplit ) ) {
				$localName = end( $elementSplit );
			} else {
				$localName = $name;
			}
			if ( in_array( $localName, $this->getAllowedRootElements() ) ) {
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
		//@TODO: Change that to the restbase URL's
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
			if ( $this->getMathStyle() === 'display' ) {
				// TODO: Improve style cleaning
				$style = preg_replace(
					'/margin\-(left|right)\:\s*\d+(\%|in|cm|mm|em|ex|pt|pc|px)\;/', '', $style
				);
			}
			$style = preg_replace( '/position:\s*absolute;\s*left:\s*0px;/', '', $style );
		}
		// TODO: Figure out if there is a way to construct
		// a SVGReader from a string that represents the SVG
		// content
		if ( preg_match( "/height=\"(.*?)\"/", $this->getSvg(), $matches ) ) {
			$style .= 'height: ' . $matches[1] . '; ';
		}
		if ( preg_match( "/width=\"(.*?)\"/", $this->getSvg(), $matches ) ) {
			$style .= 'width: ' . $matches[1] . ';';
		}
	}

	/**
	 * Gets img tag for math image
	 * @param boolean $noRender if true no rendering will be performed
	 * if the image is not stored in the database
	 * @param boolean|string $classOverride if classOverride
	 * is false the class name will be calculated by getClassName
	 * @return string XML the image html tag
	 */
	private function getFallbackImage( $noRender = false, $classOverride = false ) {
		$url = $this->getFallbackImageUrl( $noRender );

		$attribs = array();
		if ( $classOverride === false ) { // $class = '' suppresses class attribute
			$class = $this->getClassName( true );
		} else {
			$class = $classOverride;
		}

		// TODO: move the common styles to the global stylesheet!
		$style = 'background-image: url(\''. $url .
				 '\'); background-repeat: no-repeat; background-size: 100% 100%;';
		$this->correctSvgStyle( $this->getSvg(), $style );
		if ( $class ) {
			$attribs['class'] = $class;
		}

		// Don't use an empty span, as that is going to be stripped by HTML tidy
		// when enabled (which is true in production).
		return Xml::element( 'meta', $this->getAttributes(
			'span', $attribs, array( 'aria-hidden' => 'true', 'style' => $style
		) ) );
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
		if ( $this->getMathStyle() == 'display' ) {
			$class .= 'display';
		} else {
			$class .= 'inline';
		}
		if ( !$fallback ) {
			// @codingStandardsIgnoreStart
			$class .= ' mwe-math-mathml-a11y';
			// @codingStandardsIgnoreEnd
		}
		return $class;
	}

	/**
	 * @return string Html output that is embedded in the page
	 */
	public function getHtmlOutput() {
		if ( $this->getMathStyle() == 'display' ) {
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
		$mml = preg_replace(
			'/(<math[^>]*)(display|mode)=["\'](inline|block)["\']/', '$1', $this->getMathml()
		);
		if ( $this->getMathStyle() == 'display' ) {
			$mml = preg_replace( '/<math/', '<math display="block"', $mml );
		}
		$output .= Xml::tags( $element, array(
			'class' => $this->getClassName(), 'style' => 'display: none;'
		), $mml );
		$output .= $this->getFallbackImage();
		$output .= HTML::closeElement( $element );
		return $output;
	}

}
