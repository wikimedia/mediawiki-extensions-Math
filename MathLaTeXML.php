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
	const DEFAULT_LATEXML_SETTING = 'format=xhtml&whatsin=math&whatsout=math&pmml&cmml&nodefaultresources&preload=LaTeX.pool&preload=article.cls&preload=amsmath.sty&preload=amsthm.sty&preload=amstext.sty&preload=amssymb.sty&preload=eucal.sty&preload=[dvipsnames]xcolor.sty&preload=url.sty&preload=hyperref.sty&preload=[ids]latexml.sty&preload=texvc';

	/**
	 * Gets the settings for the LaTeXML daemon.
	 *
	 * @return string
	 */
	public function getLaTeXMLSettings() {
		if ( $this->LaTeXMLSettings ) {
			return $this->LaTeXMLSettings;
		} else {
			return self::DEFAULT_LATEXML_SETTING;
		}
	}

	/**
	 * Sets the settings for the LaTeXML daemon.
	 * The settings affect only the current instance of the class.
	 * For a list of possible settings see:
	 * http://dlmf.nist.gov/LaTeXML/manual/commands/latexmlpost.xhtml
	 * An empty value indicates to use the default settings.
	 * @param string $settings
	 */
	public function setLaTeXMLSettings( $settings ) {
		$this->LaTeXMLSettings = $settings;
	}

	/* (non-PHPdoc)
	 * @see MathRenderer::render()
	*/
	public function render( $forceReRendering = false ) {
		if ( $forceReRendering ) {
			$this->setPurge( true );
		}
		if ( $this->renderingRequired() ) {
			$res = $this->doRender( );
			if ( ! $res ) {
				return $this->getLastError();
			}
		}
		return $this->getMathMLTag();
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
				if ( self::isValidMathML( $this->getMathml() ) ) {
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
		global $wgLaTeXMLTimeout;
		$error = '';
		$res = null;
		$options = array( 'method' => 'POST', 'postData' => $post, 'timeout' => $wgLaTeXMLTimeout );
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
						, 'wgLaTeXMLTimeout' => $wgLaTeXMLTimeout ), true ) . "\n\n" );
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
		global $wgLaTeXMLUrl;
		if ( is_array( $wgLaTeXMLUrl ) ) {
			$host = array_rand( $wgLaTeXMLUrl );
		} else {
			$host = $wgLaTeXMLUrl;
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
		$texcmd = urlencode( $this->tex );
		return $this->getLaTeXMLSettings() . '&tex=' . $texcmd;
	}
	/**
	 * Does the actual web request to convert TeX to MathML.
	 * @return boolean
	 */
	private function doRender( ) {
		$host = self::pickHost();
		$post = $this->getPostData();
		$this->lastError = '';
		if ( $this->makeRequest( $host, $post, $res, $this->lastError ) ) {
			$result = json_decode( $res );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( self::isValidMathML( $result->result ) ) {
					$this->setMathml( $result->result );
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
	static public function isValidMathML( $XML ) {
		$out = false;
		// depends on https://gerrit.wikimedia.org/r/#/c/66365/
		if ( ! is_callable( 'XmlTypeCheck::newFromString' ) ) {
			$msg = wfMessage( 'math_latexml_xmlversion' )->inContentLanguage()->escaped();
			trigger_error( $msg, E_USER_NOTICE );
			wfDebugLog( 'Math', $msg );
			return true;
		}
		$xmlObject = new XmlTypeCheck( $XML, null, false );
		if ( ! $xmlObject->wellFormed ) {
			wfDebugLog( "Math", "XML validation error:\n " . var_export( $XML, true ) . "\n" );
		} else {
			$name = $xmlObject->getRootElement();
			$name = str_replace( 'http://www.w3.org/1998/Math/MathML:', '', $name );
			if ( $name == "math" or $name == "table" or $name == "div" ) {
				$out = true;
			} else {
				wfDebugLog( "Math", "got wrong root element " . $name );
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
	 * @param string $mml: the MathML string
	 * @param string $tagId: optional tagID for references like (pagename#equation2)
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