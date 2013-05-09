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
	 * @var String settings for LaTeXML deamon
	 */
	private $LaTeXMLSettings = '';

	/**
	 * Gets the setting for the LaTeXML deamon.
	 *
	 * @return string
	 */
	public function getLaTeXMLSettings() {
		if ( $this->LaTeXMLSettings ) {
			return $this->LaTeXMLSettings;
		} else {
			return 'format=xhtml&' .
				'whatsin=math&' .
				'whatsout=math&' .
				'pmml&' . // presentation MathML
				'cmml&' . // content MathML
				'preload=LaTeX.pool&' .
				'preload=article.cls&' .
				'preload=amsmath&' .
				'preload=amsthm&' .
				'preload=amstext&' .
				'preload=amssymb&' .
				'preload=eucal&' .
				'preload=[dvipsnames]xcolor&' .
				'preload=url&' .
				'preload=hyperref&' .
				'preload=mws&' .
				'preload=ids&' . // generate ids for parallel markup
				'preload=texvc';
		}
	}

	/**
	 * Sets the setting for the LaTeXML deamon.
	 * The settings affect only the current instance of the class.
	 * For a list of possible settings see:
	 * http://dlmf.nist.gov/LaTeXML/manual/commands/latexmlpost.xhtml
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
			$this->setPurge( $forceReRendering );
		}
		if ( $this->renderingRequired() ) {
			$res = $this->dorender($error);
			if ( ! $res ) {
				return $error;
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
					wfDebugLog( "Math", "Mal formatted entry found in database" );
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
	 * @return boolean success
	 */
	public function makeRequest( $host, $post, &$res, &$error = null ) {
		global $wgLaTeXMLTimeout;
		$error = null;
		$time_start = microtime( true );
		$res = Http::post( $host, array( "postData" => $post,
				"timeout" => $wgLaTeXMLTimeout ) );
		$time_end = microtime( true );
		$time = $time_end - $time_start;
		if ( $wgLaTeXMLTimeout > 0 && $time > $wgLaTeXMLTimeout ) {
			$error = $this->getError( 'math_latexml_timeout', $host );
			wfDebugLog( "Math", "\nLaTeXML Timeout:"
				. var_export( array( 'post' => $post, 'host' => $host
					, 'wgLaTeXMLTimeout' => $wgLaTeXMLTimeout ), true ) . "\n\n" );
			return false;
		} else {
			wfDebugLog( "Math", "Latexml request: $post\n processed in $time seconds." );
		}
		if ( $res ) {
			return true;
		} else {
			$error = $this->getError( 'math_latexml_noresponse', $host );
			wfDebugLog( "Math", "\nLaTeXML NoResponse:"
					. var_export( array( 'post' => $post, 'host' => $host ), true ) . "\n\n" );
			return false;
		}
	}
	/* (non-PHPdoc)
	 * @see MathRenderer::writeCache()
	*/
	function writeCache() {
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
			$pick = mt_rand( 0, count( $wgLaTeXMLUrl ) - 1 );
			$host = $wgLaTeXMLUrl[$pick];
		} else {
			$host = $wgLaTeXMLUrl;
		}
		wfDebugLog( "Math", "picking host " . $host );
		return $host;
	}

	/**
	 * @return boolean
	 */
	private function dorender( &$error = null ) {
		$error = null;
		$host = self::pickHost();
		$texcmd = urlencode( $this->tex );
		$post = $this->getLaTeXMLSettings();
		$post .= '&tex=' . $texcmd;
		if ( $this->makeRequest( $host, $post, $res, $error ) ) {
			$result = json_decode( $res );
			if ( json_last_error() === JSON_ERROR_NONE ) {
				if ( self::isValidMathML( $result->result ) ) {
					$this->setMathml( $result->result );
					return true;
				} else {
					// Do not print bad mathml. It's probably too verbose and might
					// mess up the browser output.
					$error = $this->getError( 'math_latexml_invalidxml', $host );
					wfDebugLog( "Math", "\nLaTeXML InvalidMathML:"
						. var_export( array( 'post' => $post, 'host' => $host
							, 'result' => $result ), true ) . "\n\n" );
					return false;
				}
			} else {
					$error = $this->getError( 'math_latexml_invalidjson', $host );
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
		// TODO: Check: Is simpleXML core php?
		//	Is libxml_use_internal_error permanent (side effects with other methods)?
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $XML );
		if ( !$xml ) {
			wfDebugLog( "Math", "XML validation error:\n " . var_export( $XML, true ) . "\n" );
			foreach ( libxml_get_errors() as $error ) {
				wfDebugLog( "Math", "\t" . $error->message );
			}
			libxml_clear_errors();
			return false;
		} else {
			$name = $xml->getName();
			if ( $name == "math" or $name == "table" or $name == "div" ) {
				return true;
			} else {
				wfDebugLog( "Math", "got wrong root element " . $name );
				return false;
			}
		}
	}

	/**
	 * Internal version of @link self::embedMathML
	 * @return string
	 * @return html element with rendered math
	 */
	private function getMathMLTag() {
		return self::embedMathML( $this->getMathml()
			, urldecode( $this->getTex() ) );
	}

	/**
	 * Embedds the MathML-XML element in a HTML span element with class tex
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