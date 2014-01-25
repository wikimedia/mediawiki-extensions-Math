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
 * Abstract base class with static methods for rendering the <math> tags using
 * different technologies. These static methods create a new instance of the
 * extending classes and render the math tags based on the mode setting of the user.
 * Furthermore this class handles the caching of the rendered output and provides
 *  debug information,
 * if run in mathdebug mode.
 *
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
 * @author Moritz Schubotz
 */
abstract class MathRenderer {

	// REPRESENTATIONS OF THE MATHEMATCAL CONTENT
	/** @var string tex representation */
	protected $tex = '';
	/** @var string MathML content and presentation */
	protected $mathml = '';
	/** @var string SVG layout only (no semantics)*/
	protected $svg = '';
	/** @var string the original user input string (which was used to caculate the inputhash) */
	protected $userInputTex = '';
	/** @var array with user-defined parameters passed to the extension (not used) */
	protected $params = '';

	// STATE OF THE CLASS INSTANCE
	/** @var boolean has variable tex been security-checked */
	protected $texSecure = false;
	/** @var boolean has the mathtematical content changed */
	protected $changed = false;
	/** @var boolean is there a database entry for the mathematical content */
	protected $storedInDatabase = null;
	/** @var boolean is there a request to purge the existing mathematical content */
	protected $purge = false;
	/** @var string with last occurred error */
	protected $lastError = '';
	/** @var string md5 value from userInputTex */
	protected $md5 = '';
	/** @var binary packed inputhash */
	protected $inputHash = '';
	/** @var int rendering mode MW_MATH_(PNG|MATHML|SOURCE...)*/
	protected $mode = MW_MATH_PNG;

	/**
	 * Constructs a base MathRenderer
	 *
	 * @param string $tex (optional) LaTeX markup
	 * @param array $params (optional) HTML attributes
	 */
	public function __construct( $tex = '', $params = array() ) {
		$this->userInputTex = $tex;
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
		$renderer = self::getRenderer( $tex, $params, $mode );
		return $renderer->render();
	}

	/**
	 * Static factory method for getting a renderer based on mode
	 *
	 * @param string $tex LaTeX markup
	 * @param array $params HTML attributes
	 * @param int $mode constant indicating rendering mode
	 * @return MathRenderer appropriate renderer for mode
	 */
	public static function getRenderer( $tex, $params = array(),  $mode = MW_MATH_PNG ) {
		global $wgDefaultUserOptions, $wgMathValidModes;
		if ( !in_array( $mode, $wgMathValidModes ) ) {
			$mode = $wgDefaultUserOptions['math'];
		}
		switch ( $mode ) {
			case MW_MATH_MATHJAX:
			case MW_MATH_SOURCE:
				$renderer = new MathSource( $tex, $params );
				break;
			case MW_MATH_LATEXML:
				$renderer = new MathLaTeXML( $tex, $params );
				break;
			case MW_MATH_PNG:
			default:
				$renderer = new MathTexvc( $tex, $params );
		}
		wfDebugLog ( "Math", 'start rendering $' . $renderer->tex . '$ in mode ' . $mode );
		return $renderer;
	}

	/**
	 * Performs the rendering and returns the rendered element that needs to be embedded.
	 *
	 * @return string of rendered HTML
	 */
	abstract public function render();


	/**
	 * texvc error messages
	 * TODO: update to MathML
	 * Returns an internationalized HTML error string
	 *
	 * @param string $msg message key for specific error
	 * @param Varargs $parameters (optional) zero or more message parameters for specific error
	 * @return string HTML error string
	 */
	public function getError( $msg /*, ... */ ) {
		$mf = wfMessage( 'math_failure' )->inContentLanguage()->escaped();
		$parameters = func_get_args();
		array_shift( $parameters );
		$errmsg = wfMessage( $msg, $parameters )->inContentLanguage()->escaped();
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return "<strong class='error texerror'>$mf($errmsg): $source</strong>\n";
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getMd5() {
		if ( $this->md5 ) {
			return $this->md5;
		} else {
			return md5( $this->userInputTex );
		}
	}
	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getInputHash() {
		if ( !$this->inputHash ) {
			$dbr = wfGetDB( DB_SLAVE );
			return $dbr->encodeBlob( pack( "H32", $this->getMd5() ) ); # Binary packed, not hex
		}
		return $this->inputHash;
	}

	/**
	 * Decode binary packed hash from the database to md5 of input_tex
	 * @param binary $hash
	 * @return string md5
	 */
	private static function dbHash2md5( $hash ) {
		$dbr = wfGetDB( DB_SLAVE );
		$xhash = unpack( 'H32md5', $dbr->decodeBlob( $hash ) . "                " );
		return $xhash['md5'];
	}

	/**
	 * Reads rendering data from database
	 *
	 * @return boolean true if read successfully, false otherwise
	 */
	public function readFromDatabase() {
		wfProfileIn( __METHOD__ );
		/** @var DatabaseBase */
		$dbr = wfGetDB( DB_SLAVE );
		/** @var ResultWrapper */
		$rpage = $dbr->selectRow( $this->getMathTableName(),
			$this->dbInArray(),
			array( 'math_inputhash' => $this->getInputHash() ),
			__METHOD__ );
		if ( $rpage !== false ) {
			$this->initializeFromDatabaseRow( $rpage );
			$this->storedInDatabase = true;
			return true;
		} else {
			# Missing from the database and/or the render cache
			$this->storedInDatabase = false;
			wfProfileOut( __METHOD__ );
			return false;
		}
	}

	/**
	 * @return array with the database column names
	 */
	protected function dbInArray() {
		$in = array( 'math_inputhash',
			'math_mathml',
			'math_inputtex',
			'math_tex',
			'math_svg'
			);
		return $in;
	}

	/**
	 *
	 * @param database_row $rpage
	 */
	protected function initializeFromDatabaseRow( $rpage ) {
		$this->inputHash = $rpage->math_inputhash; // MUST NOT BE NULL
		$this->md5 = self::dbHash2md5( $this->inputHash );
		if ( ! empty( $rpage->math_mathml ) ) {
			$this->mathml = utf8_decode ( $rpage->math_mathml );
		}
		if ( ! empty( $rpage->math_inputtex ) ) { // in the current database the field is probably not set.
			$this->userInputTex = $rpage->math_inputtex;
		}
		if ( ! empty( $rpage->math_tex ) ) {
			$this->tex = $rpage->math_tex;
		}
		if ( ! empty( $rpage->math_svg ) ) {
			$this->svg = $rpage->math_svg;
		}
		$this->changed = false;
	}


	/**
	 * Writes rendering entry to database.
	 *
	 * WARNING: Use writeCache() instead of this method to be sure that all
	 * renderer specific (such as squid caching) are taken into account.
	 * This function stores the values that are currently present in the class to the database even if they are empty.
	 *
	 * This function can be seen as protected function.
	 */
	public function writeToDatabase( $dbw = null ) {
		# Now save it back to the DB:
		if ( !wfReadOnly() ) {
			$dbw = $dbw ? : wfGetDB( DB_MASTER );
			wfDebugLog( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . $this->getMd5() . ")\n" );
			$outArray = $this->dbOutArray();
			$method = __METHOD__;
			$mathTableName = $this->getMathTableName();
			if ( $this->isInDatabase() ) {
				$inputHash = $this->getInputHash();
				$dbw->onTransactionIdle(
					function() use( $dbw, $outArray, $inputHash, $method, $mathTableName ) {
						$dbw->update( $mathTableName, $outArray , array( 'math_inputhash' => $inputHash ), $method );
					} );
			} else {
				$dbw->onTransactionIdle(
					function() use( $dbw, $outArray, $method, $mathTableName ) {
						$dbw->insert( $mathTableName, $outArray, $method , array ( 'IGNORE' ) );
					} );
			}
			$this->changed = false;
			$this->storedInDatabase = true;
		}
	}

	/**
	 * Gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	protected function dbOutArray() {
		$out = array( 'math_inputhash' => $this->getInputHash (),
			'math_mathml' => utf8_encode ( $this->mathml ),
			'math_inputtex' => $this->userInputTex,
			'math_tex' => $this->tex,
			'math_svg' => $this->svg
			);
		return $out;
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
		$attribs = Sanitizer::validateTagAttributes( $this->params, $tag );
		$attribs = Sanitizer::mergeAttributes( $defaults, $attribs );
		$attribs = Sanitizer::mergeAttributes( $attribs, $overrides );
		return $attribs;
	}


	/**
	 * Writes cache. Writes the database entry if values were changed
	 */
	public function writeCache() {
		if ( $this->isChanged() ) {
			$this->writeToDatabase();
		}
	}

	/**
	 * Determines if this is a cached/recalled render
	 *
	 * @return boolean true if recalled, false otherwise
	 */
	public function isRecall() {
		return $this->recall;
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
	 * gets the rendering mode MW_MATH_*
	 *
	 * @return int
	 */
	public function getMode() {
		return $this->mode;
	}

	/**
	 * Sets the TeX code
	 *
	 * @param string $tex
	 */
	public function setTex( $tex ) {
		$this->changed = true;
		$this->tex = $tex;
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
	 * Gets the MathML XML element
	 * @return string in UTF-8 encoding
	 */
	public function getMathml() {
		if ( ! is_callable( 'StringUtils::isUtf8' ) ) {
			$msg = wfMessage( 'math_latexml_xmlversion' )->inContentLanguage()->escaped();
			trigger_error( $msg, E_USER_NOTICE );
			wfDebugLog( 'Math', $msg );
			// If we can not check if mathml output is valid, we skip the test and assume that it is valid.
		} elseif ( ! StringUtils::isUtf8( $this->mathml ) ) {
			$this->setMathml( '' );
		}
		return $this->mathml;
	}

	/**
	 * @param string $mathml use UTF-8 encoding
	 */
	public function setMathml( $mathml ) {
		$this->changed = true;
		$this->mathml = $mathml;
	}

	/**
	 * Gets the so called 'conservativeness' calculated by texvc
	 *
	 * @return int
	 */
	public function getConservativeness() {
		return $this->conservativeness;
	}

	/**
	 * @param int $conservativeness
	 */
	public function setConservativeness( $conservativeness ) {
		$this->changed = true;
		$this->conservativeness = $conservativeness;
	}

	/**
	 * Get the attributes of the math tag
	 *
	 * @return array()
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @param array() $params
	 */
	public function setParams( $params ) {
		// $changed is not set to true here, because the attributes do not affect
		// the rendering in the current implementation.
		// If this behavior will change in the future $this->tex is no longer a
		// primary key and the input hash cannot be calculate form $this->tex
		// only. See the discussion 'Tag extensions in Block mode' on wikitech-l.
		$this->params = $params;
	}

	/**
	 * Checks if the instance was modified i.e., because math was rendered
	 *
	 * @return boolean true if something was changed false otherwise
	 */
	public function isChanged() {
		return $this->changed;
	}

	/**
	 * Checks if there is an explicit user request to rerender the math-tag.
	 * @return boolean
	 */
	function isPurge( ) {
		if ( $this->purge ) {
			return true;
		}
		// TODO: Figure out if ?action=purge
		// until this issue is resolved we use ?mathpurge=true instead
		global $wgRequest;
		return ( $wgRequest->getVal( 'mathpurge' ) === "true" );
	}

	/**
	 * Sets purge. If set to true the render is forced to rerender and must not
	 * use a cached version.
	 * @return boolean
	 */
	function setPurge( $purge = true ) {
		$this->changed = true;
		$this->purge = $purge;
	}

	function getLastError() {
		return $this->lastError;
	}

	/**
	 * Get if the input tex was marked as secure
	 * @return boolean
	 */
	public function isTexSecure() {
		return $this->texSecure;
	}

	public function checkTex() {
		if ( !$this->texSecure ) {
			$checker = new MathInputCheckTexvc( $this->userInputTex );
			if ( $checker->isValid() ){
				$this->setTex( $checker->getValidTex() );
				$this->texSecure = true;
				return true;
			} else {
				$this->lastError = $checker->getError();
				return false;
			}
		}
	}

	/**
	 * @return string original tex string specified by the user
	 */
	public function getUserInputTex(){
		return $this->userInputTex;
	}
	protected function getMathTableName() {
		return 'mathoid';
	}

	public function isInDatabase() {
		if ( $this->storedInDatabase === null ) {
			$this->readFromDatabase();
		}
		return $this->storedInDatabase;
	}


}

