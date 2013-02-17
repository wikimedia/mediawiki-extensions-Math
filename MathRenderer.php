<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * @file
 */

/**
 * Abstract base class for math renderers using different technologies.
 *
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
 * @author Moritz Schubotz
 */
abstract class MathRenderer {
	/**
	 *  The following variables should made private, as soon it can be verified
	 *  that they are not being directly accessed by other extensions.
	 */
	var $mode = MW_MATH_PNG;
	var $tex = '';
	/**
	 * can be calculated from the tex code
	 * @var binary
	 */
	var $inputhash = '';
	/**
	 * is calculated by texvc.
	 * @deprecated
	 * @var binary
	 */
	var $hash = '';
	var $html = '';
	var $mathml = '';
	var $conservativeness = 0;
	var $params = '';
	// DEBUG variables
	var $log = '';
	var $status_code = '';
	var $valid_xml = '';
	var $success = false;
	var $timestamp;
	var $storedInDatabase = false;
	var $changed = false;

	/**
	 * Constructs a base MathRenderer
	 *
	 * @param string $tex LaTeX markup
	 * @param array $params HTML attributes
	 */
	public function __construct( $tex, $params = array() ) {
		$this->tex = $tex;
		$this->params = $params;
	}

	/**
	 * Static method for rendering math tag
	 *
	 * @param string $tex LaTeX markup
	 * @param array $params HTML attributes
	 * @param int $mode constant indicating rendering mode
	 * @return string HTML for math tag
	 */
	public static function renderMath( $tex, $params = array(), $mode = MW_MATH_PNG ) {
		$renderer = self::getRenderer ( $tex, $params, $mode );
		return $renderer->render ();
	}

	/**
	 * Static factory method for getting a renderer based on mode
	 *
	 * @param string $tex LaTeX markup
	 * @param array $params HTML attributes
	 * @param int $mode constant indicating rendering mode
	 * @return MathRenderer appropriate renderer for mode
	 */
	public static function getRenderer( $tex, $params = array(), $mode = MW_MATH_PNG ) {
		global $wgDefaultUserOptions;
		$validModes = array( MW_MATH_PNG, MW_MATH_SOURCE, MW_MATH_MATHJAX );
		if ( !in_array ( $mode, $validModes ) )
			$mode = $wgDefaultUserOptions['math'];
		switch ( $mode ) {
		case MW_MATH_SOURCE:
			$renderer = new MathSource ( $tex, $params );
			break;
		case MW_MATH_MATHJAX:
			$renderer = new MathMathJax ( $tex, $params );
			break;
		case MW_MATH_PNG:
		default:
			$renderer = new MathTexvc ( $tex, $params );
		}
		wfDebugLog ( "Math", 'start rendering $' . $renderer->tex . '$' );
		return $renderer;
	}

	/**
	 * Performs the rendering and returns the rendered element that needs to be embedded.
	 *
	 * @return string of rendered HTML
	 */
	abstract public function render();

	/**
	 * Returns an internationalized HTML error string
	 *
	 * @param string $msg message key for specific error
	 * @param string $append string to append after error
	 * @return string HTML error string
	 */
	protected function getError( $msg, $append = '' ) {
		$mf = wfMessage ( 'math_failure' )->inContentLanguage ()->escaped ();
		$errmsg = wfMessage ( $msg )->inContentLanguage ()->escaped ();
		$source = htmlspecialchars ( str_replace ( "\n", ' ', $this->tex ) );
		return "<strong class='error'>$mf ($errmsg$append): $source</strong>\n";
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getInputHash() {
		// TODO: What happens if $tex is empty?
		if ( $this->inputhash == '' ) {
			$dbr = wfGetDB ( DB_SLAVE );
			return $dbr->encodeBlob ( pack ( "H32", md5 ( $this->tex ) ) ); # Binary packed, not hex
		} else {
			return $this->inputhash;
		}
	}
	public function initializeFromDatabaseRow( $rpage ) {
		global $wgDebugMath;
		$dbr = wfGetDB ( DB_SLAVE );
		$xhash = unpack ( 'H32md5',
			$dbr->decodeBlob ( $rpage->math_outputhash ) . "                " );
		$this->hash = $xhash['md5'];
		$this->conservativeness = $rpage->math_html_conservativeness;
		$this->html = $rpage->math_html;
		$this->mathml = utf8_decode ( $rpage->math_mathml );
		$this->storedInDatabase = true;
		if ( $wgDebugMath ) {
			$dbtex = $rpage->math_tex;
			if ( $dbtex != $this->tex ) {
				if ( $this->tex != "" ) {
					wfDebugLog ( "Math",
						"WARNING database text is $dbtex whereas" . " input text was" . $this->tex );
				} else {
					$this->tex = $dbtex;
				}
			}
			$this->status_code = $rpage->math_status;
			$this->valid_xml = $rpage->valid_xml;
			$this->log = $rpage->math_log;
			$this->timestamp = $rpage->math_timestamp;
		}
	}
	/**
	 * @return array with the database column names
	 */
	private function dbInArray() {
		global $wgDebugMath;
		$in = array( 'math_inputhash', 'math_outputhash', 'math_html_conservativeness', 'math_html',
			'math_mathml' );
		if ( $wgDebugMath ) {
			$debug_in = array( 'math_status', 'valid_xml', 'math_tex', 'math_log', 'math_timestamp' );
			$in = array_merge ( $in, $debug_in );
		}
		return $in;
	}
	/**
	 * Reads rendering data from database
	 *
	 * @return boolean true if read successfully, false otherwise
	 */
	public function readDatabaseEntry() {
		$dbr = wfGetDB ( DB_SLAVE );
		$rpage = $dbr
			->selectRow ( 'math', $this->dbInArray (),
				array( 'math_inputhash' => $this->getInputHash () ), __METHOD__ );
		if ( $rpage !== false ) {
			$this->initializeFromDatabaseRow ( $rpage );
			return true;
		} else {
			# Missing from the database and/or the render cache
			$this->storedInDatabase = false;
			return false;
		}
	}
	/**
	 * gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	private function dbOutArray() {
		global $wgDebugMath;
		$dbr = wfGetDB ( DB_SLAVE );
		if ( $this->hash )
			$outmd5_sql = $dbr->encodeBlob ( pack ( 'H32', $this->hash ) );
		else
			$outmd5_sql = 0; // field cannot be null
		// TODO: Change Database layout to allow for null values
		$out = array( 'math_inputhash' => $this->getInputHash (), 'math_outputhash' => $outmd5_sql,
			'math_html_conservativeness' => $this->conservativeness, 'math_html' => $this->html,
			'math_mathml' => utf8_encode ( $this->mathml ) );
		if ( $wgDebugMath ) {
			$debug_out = array( 'math_status' => $this->status_code,
				'valid_xml' => $this->valid_xml, 'math_tex' => $this->tex,
				'math_log' => $this->log );
			$out = array_merge ( $out, $debug_out );
		}
		wfDebugLog ( "Math", "Store Data:" . var_export ( $out, true ) . "\n\n" );
		return $out;
	}
	/**
	 * Writes rendering entry to database.
	 *
	 * WARNING: Use writeCache() if instead of this method to be sure that all
	 * renderer specific (such as squid caching) are taken into account.
	 * This function stores the values that are currently present in the class to the database even if they are empty.
	 *
	 * This function can be seen as protected function.
	 */
	public function writeDatabaseEntry( $dbw = null ) {
		# Now save it back to the DB:
		if ( !wfReadOnly () ) {
			if ( $dbw == null ) {
				$dbw = wfGetDB ( DB_MASTER );
			}
			wfDebugLog ( "Math",
				'store entry for $' . $this->tex . '$ in database (hash:' . $this->getInputHash ()
					. ')\n' );
			$outArray = $this->dbOutArray ();
			$dbw
				->onTransactionIdle (
					function () use ( $dbw, $outArray ) {
						$dbw->replace ( 'math', array( 'math_inputhash' ), $outArray, __METHOD__ );
					} );
		}
	}

	/**
	 * Returns sanitized attributes
	 *
	 * @param string $tag element name
	 * @param array $defaults default attributes
	 * @param array $overrides attributes to override defaults
	 * @return array HTML attributes
	 */
	protected function getAttributes( $tag, $defaults = array(), $overrides = array() ) {
		$attribs = Sanitizer::validateTagAttributes ( $this->params, $tag );
		$attribs = Sanitizer::mergeAttributes ( $defaults, $attribs );
		$attribs = Sanitizer::mergeAttributes ( $attribs, $overrides );
		return $attribs;
	}
	/**
	 * Writes cache.  Writes the database entry if values were changed
	 */
	public function writeCache() {
		if ( $this->wasChanged() ) {
			$this->writeDatabaseEntry();
		}
	}

	/**
	 * Determines if the class instance was changed.
	 * e.g. to determine if the oject needs to be stored in the databse
	 *
	 * @return boolean true if recalled, false otherwise
	 */
	public function wasChanged() {
		return $this->changed;
	}
	/**
	 * returns true if the rendering was successful
	 * @return boolean
	 */
	public function getSuccess() {
		return $this->success;
	}
	/**
	 *@param boolean $b
	 */
	public function setSuccess( $b ) {
		$this->success = $b;
	}

	/**
	 * Gets TeX markup
	 *
	 * @return string TeX markup
	 */
	public function getTex() {
		return $this->tex;
	}
	/**
	 * get the timestamp, of the last rending of that equation
	 * @return int
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @return int
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	 * @param int $mode
	 */
	public function setMode( $mode ) {
		$this->mode = $mode;
	}

	/**
	 * @param string $tex
	 */
	public function setTex( $tex ) {
		$this->changed = true;
		$this->tex = $tex;
	}

	/**
	 * @param binary $inputhash
	 */
	public function setInputhash( $inputhash ) {
		$this->changed = true;
		$this->inputhash = $inputhash;
	}

	/**
	 * @return the binary hash
	 * @deprecated
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @param binary $hash
	 * @deprecated
	 */
	public function setHash( $hash ) {
		$this->changed = true;
		$this->hash = $hash;
	}

	/**
	 * Returns the html-representation of the mathematical formula.
	 * @return string
	 */
	public function getHtml() {
		return $this->html;
	}

	/**
	 * @param string $html
	 */
	public function setHtml( $html ) {
		$this->changed = true;
		$this->html = $html;
	}

	/**
	 * @return string
	 */
	public function getMathml() {
		return $this->mathml;
	}

	/**
	 * @param string $mathml
	 */
	public function setMathml( $mathml ) {
		$this->changed = true;
		$this->mathml = $mathml;
	}

	/**
	 * @deprecated
	 * @return int
	 */
	public function getConservativeness() {
		return $this->conservativeness;
	}

	/**
	 * @param int $conservativeness
	 * @deprecated
	 */
	public function setConservativeness( $conservativeness ) {
		$this->changed = true;
		$this->conservativeness = $conservativeness;
	}

	/**
	 * @return array()
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @param array() $params
	 */
	public function setParams( $params ) {
		$this->params = $params;
	}

	/**
	 * @return string
	 */
	public function getLog() {
		return $this->log;
	}

	/**
	 * @param string $log
	 */
	public function setLog( $log ) {
		$this->changed = true;
		$this->log = $log;
	}

	/**
	 * @return int
	 */
	public function getStatusCode() {
		return $this->status_code;
	}

	/**
	 * @param unknown_type $status_code
	 */
	public function setStatusCode( $status_code ) {
		$this->changed = true;
		$this->status_code = $status_code;
	}

	/**
	 * @return boolean
	 */
	public function getValidXml() {
		return $this->valid_xml;
	}

	/**
	 * @param boolean $valid_xml
	 */
	public function setValidXml( $valid_xml ) {
		$this->changed = true;
		$this->valid_xml = $valid_xml;
	}

	/**
	 * @return booleane
	 */
	public function getSuccess() {
		return $this->success;
	}

	/**
	 * @param int $timestamp
	 */
	public function setTimestamp( $timestamp ) {
		$this->changed = true;
		$this->timestamp = $timestamp;
	}

}
