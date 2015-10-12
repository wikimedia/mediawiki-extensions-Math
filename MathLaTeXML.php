<?php
use MediaWiki\Logger\LoggerFactory;

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
	protected $defaultAllowedRootElements = array( 'math', 'div', 'table', 'query' );
	/** @var String settings for LaTeXML daemon */
	private $LaTeXMLSettings = '';

	public function __construct( $tex = '', $params = array() ) {
		global $wgMathLaTeXMLUrl;
		parent::__construct( $tex, $params );
		$this->hosts = $wgMathLaTeXMLUrl;
		$this->setMode( 'latexml' );
	}
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
	 * Calculates the HTTP POST Data for the request. Depends on the settings
	 * and the input string only.
	 * @return string HTTP POST data
	 */
	public function getLaTeXMLPostData() {
		$tex = $this->getTex();
		if ( $this->getMathStyle() == 'inlineDisplaystyle' ) {
			// In 'inlineDisplaystyle' the old
			// texvc behavior is reproduced:
			// The equation is rendered in displaystyle
			// (texvc used $$ $tex $$ to render)
			// but the equation is not centered.
			$tex = '{\displaystyle ' . $tex . '}';
		}
		$texcmd = rawurlencode( $tex );
		$settings = $this->serializeSettings( $this->getLaTeXMLSettings() );
		$postData = $settings . '&tex=' . $texcmd;
		LoggerFactory::getInstance( 'Math' )->debug( 'Get post data: ' . $postData );
		return $postData;
	}

	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return boolean
	 */
	protected function doRender() {
		if ( trim( $this->getTex() ) === '' ) {
			LoggerFactory::getInstance( 'Math' )->warning(
				'Rendering was requested, but no TeX string is specified.' );
			$this->lastError = $this->getError( 'math_empty_tex' );
			return false;
		}
		$res = '';
		$host = $this->pickHost();
		$post = $this->getLaTeXMLPostData();
		// There is an API-inconsistency between different versions of the LaTeXML daemon
		// some versions require the literal prefix other don't allow it.
		if ( ! strpos( $host, '/convert' ) ){
			$post = preg_replace( '/&tex=/', '&tex=literal:', $post, 1 );
		}
		$this->lastError = '';
		$requestResult = $this->makeRequest( $host, $post, $res, $this->lastError );
		if ( $requestResult ) {
			$jsonResult = json_decode( $res );
			if ( $jsonResult && json_last_error() === JSON_ERROR_NONE ) {
				if ( $this->isValidMathML( $jsonResult->result ) ) {
					$this->setMathml( $jsonResult->result );
					Hooks::run( 'MathRenderingResultRetrieved',
						array( &$this,
							   &$jsonResult ) );// Enables debugging of server results
					return true;
				} else {
					// Do not print bad mathml. It's probably too verbose and might
					// mess up the browser output.
					$this->lastError = $this->getError( 'math_invalidxml', $this->getModeStr(), $host );
					LoggerFactory::getInstance( 'Math' )->warning(
						'LaTeXML InvalidMathML: ' . var_export( array(
							'post' => $post,
							'host' => $host,
							'result' => $res
						), true ) );
					return false;
				}
			} else {
				$this->lastError = $this->getError( 'math_invalidjson', $this->getModeStr(), $host );
				LoggerFactory::getInstance( 'Math' )->warning(
					'LaTeXML InvalidJSON:' . var_export( array(
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
	 * Internal version of @link self::embedMathML
	 * @return string
	 * @return html element with rendered math
	 */
	protected function getMathMLTag() {
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

	/**
	 * Calculates the SVG image based on the MathML input
	 * No cache is used.
	 * @return boolean
	 */
	public function calculateSvg() {
		$renderer = new MathMathML( $this->getTex() );
		$renderer->setMathml( $this->getMathml() );
		$renderer->setMode( 'latexml' );
		$res = $renderer->render( true );
		if ( $res == true ) {
			$this->setSvg( $renderer->getSvg() );
		} else {
			$lastError = $renderer->getLastError();
			LoggerFactory::getInstance( 'Math' )->error(
				'Failed to convert LaTeXML-MathML to SVG:' . $lastError );
		}
		return $res;
	}


	/**
	 * Gets the SVG image
	 *
	 * @param string $render if set to 'render' (default) and no SVG image exists, the function
	 *                       tries to generate it on the fly.
	 *                       Otherwise, if set to 'cached', and there is no SVG in the database
	 *                       cache, an empty string is returned.
	 *
	 * @return string XML-Document of the rendered SVG
	 */
	public function getSvg( $render = 'render' ) {
		if ( $render == 'render' && ( $this->isPurge() || $this->svg == '' ) ) {
			$this->calculateSvg();
		}
		return parent::getSvg( $render );
	}

	protected function getMathTableName() {
		return 'mathlatexml';
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
	public function makeRequest(
		$host, $post, &$res, &$error = '', $httpRequestClass = 'MWHttpRequest'
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

	protected function dbOutArray() {
		$out = parent::dbOutArray();
		if ( $this->getMathTableName() == 'mathoid' ) {
			$out['math_input'] = $out['math_inputtex'];
			unset( $out['math_inputtex'] );
		}
		return $out;
	}

	protected function dbInArray() {
		$out = parent::dbInArray();
		if ( $this->getMathTableName() == 'mathoid' ) {
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
}

