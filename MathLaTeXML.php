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
	protected static $DEFAULT_ALLOWED_ROOT_ELEMENTS = array( 'math', 'div', 'table', 'query' );
	/** @var String settings for LaTeXML daemon	 */
	private $LaTeXMLSettings = '';
	/** @var boolean if false LaTeXML output is not validated*/
	private $XMLValidation = true;

	public function __construct( $tex = '', $params = array() ) {
		global $wgMathLaTeXMLUrl;
		parent::__construct( $tex, $params );
		$this->hosts = $wgMathLaTeXMLUrl;
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
	 * Calculates the HTTP POST Data for the request. Depends on the settings
	 * and the input string only.
	 * @return string HTTP POST data
	 */
	public function getLaTeXMLPostData() {
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
		$host = $this->pickHost();
		$post = $this->getLaTeXMLPostData();
		// There is an API-inconsistency between different versions of the LaTeXML damon
		// some versions require the literal prefix other don't allow it.
		if ( ! strpos( $host, '/convert' ) ){
			$post = preg_replace( '/&tex=/' , '&tex=literal:', $post , 1);
		}
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

	/**
	 * Caclualates the SVG image based on the MathML input
	 * No cache is used.
	 * @return boolean
	 */
	public function calulateSvg() {
		$renderer = new MathMathML( $this->getTex() );
		$renderer->setMathml( $this->getMathml() );
		$renderer->setMode( MW_MATH_LATEXML );
		$renderer->setPurge( true );
		$res = $renderer->render();
		if ( $res == true ) {
			$this->svg = $renderer->getSvg();
		} else {
			$lastError = $renderer->getLastError();
			wfDebugLog( 'Math', 'failed to convert LaTeXML-MathML to SVG:' . $lastError );
		}

		return $res;
	}
	/**
	 * gets the svg image... supports lazy evaluation
	 * @return string XML-Document of the rendered SVG
	 */
	public function getSvg() {
		if ( $this->isPurge() || $this->svg == ''  ) {
			$this->calulateSvg();
		}
		return $this->svg;
	}
	protected function getMathTableName() {
		return 'math_latexml';
	}
}
