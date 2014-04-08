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
	/**
	 *  The following variables should made private, as soon it can be verified
	 *  that they are not being directly accessed by other extensions.
	 */
	protected $mode = MW_MATH_PNG;
	protected $tex = '';
	/** @var string the original user input string (which was used to caculate the inputhash) */
	protected $userInputTex = '';
	/** @var (MW_MATHSTYLE_INLINE_DISPLAYSTYLE|MW_MATHSTYLE_DISPLAY|MW_MATHSTYLE_INLINE) the rendering style */
	protected $mathStyle = MW_MATHSTYLE_INLINE_DISPLAYSTYLE;
	/**
	 * is calculated by texvc.
	 * @var string
	 */
	protected $hash = '';
	protected $html = '';
	protected $mathml = '';
	protected $conservativeness = 0;
	protected $params = '';
	// STATE OF THE CLASS INSTANCE
	/** @var boolean has variable tex been security-checked */
	protected $texSecure = false;
	protected $changed = false;
	/**
	 * @var boolean forces rerendering if set to true
	 */
	protected $purge = false;
	protected $recall;
	protected $lastError = '';

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
		$mathStyle = null;
		if ( isset( $params['display'] ) ) {
			$layoutMode = $params['display'];
			if ( $layoutMode == 'block' ) {
				$mathStyle = MW_MATHSTYLE_DISPLAY ;
				// TODO: Implement caching for attributes of the math tag
				// Currently the key for the database entry relating to an equation
				// is md5($tex) the new option to determine if the tex input
				// is rendered in displaystyle or textstyle would require a database
				// layout change to use a composite key e.g. (md5($tex),$mathStyle).
				// As a workaround we use the prefix \displaystyle so that the key becomes
				// md5((\{\\displaystyle|\{\\textstyle)?\s?$tex\}?)
				// The new value of $tex string describes now how the rendering should look like.
				// The variable MathRenderer::mathStyle determines if the rendered equation should
				// be centered in a new line, or just in be displayed in the current line.
				$tex = '{\displaystyle ' . $tex . '}';
			} elseif ( $layoutMode == 'inline' ) {
				$mathStyle = MW_MATHSTYLE_INLINE;
				$tex = '{\textstyle ' . $tex . '}';
			}
		}
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
		$renderer->setMathStyle( $mathStyle );
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
	 * @internal param \Varargs $parameters (optional) zero or more message parameters for specific error
	 * @return string HTML error string
	 */
	public function getError( $msg /*, ... */ ) {
		$mf = wfMessage( 'math_failure' )->inContentLanguage()->escaped();
		$parameters = func_get_args();
		array_shift( $parameters );
		$errmsg = wfMessage( $msg, $parameters )->inContentLanguage()->escaped();
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return "<strong class='error texerror'>$mf ($errmsg): $source</strong>\n";
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getInputHash() {
		// TODO: What happens if $tex is empty?
		$dbr = wfGetDB( DB_SLAVE );
		return $dbr->encodeBlob( pack( "H32", md5( $this->getUserInputTex() ) ) ); # Binary packed, not hex
	}

	/**
	 * Reads rendering data from database
	 *
	 * @return boolean true if read successfully, false otherwise
	 */
	public function readFromDatabase() {
		wfProfileIn( __METHOD__ );
		$dbr = wfGetDB( DB_SLAVE );
		$rpage = $dbr->selectRow( 'math', $this->dbInArray(),
			array( 'math_inputhash' => $this->getInputHash() ), __METHOD__ );
		if ( $rpage !== false ) {
			$this->initializeFromDatabaseRow( $rpage );
			if ( ! is_callable( 'StringUtils::isUtf8' ) ) {
				$msg = wfMessage( 'math_latexml_xmlversion' )->inContentLanguage()->escaped();
				trigger_error( $msg, E_USER_NOTICE );
				wfDebugLog( 'Math', $msg );
				// If we can not check if mathml output is valid, we skip the test and assume that it is valid.
				$this->recall = true;
				wfProfileOut( __METHOD__ );
				return true;
			} elseif ( StringUtils::isUtf8( $this->mathml ) ) {
				$this->recall = true;
				wfProfileOut( __METHOD__ );
				return true;
			}
		}

		# Missing from the database and/or the render cache
		$this->recall = false;
		wfProfileOut( __METHOD__ );
		return false;
	}
	/**
	 *
	 * @param database_row $rpage
	 */
	public function initializeFromDatabaseRow( $rpage ) {
		$dbr = wfGetDB( DB_SLAVE );
		$xhash = unpack( 'H32md5',
			$dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
		$this->hash = $xhash['md5'];
		$this->conservativeness = $rpage->math_html_conservativeness;
		$this->html = $rpage->math_html;
		$this->mathml = utf8_decode( $rpage->math_mathml );
		$this->storedInDatabase = true;
	}

	/**
	 * @return array with the database column names
	 */
	private function dbInArray() {
		return array( 'math_inputhash', 'math_outputhash', 'math_html_conservativeness', 'math_html',
				'math_mathml' );
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
			wfDebugLog( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . bin2hex( $this->hash ) . ")\n" );
			$outArray = $this->dbOutArray();
			$dbw->onTransactionIdle(
					function() use( $dbw, $outArray ) {
						$dbw->replace( 'math', array( 'math_inputhash' ), $outArray, __METHOD__ );
					} );
		}
	}

	/**
	 * Gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	private function dbOutArray() {
		$dbr = wfGetDB( DB_SLAVE );
		if ( $this->hash ) {
			$outmd5_sql = $dbr->encodeBlob( pack( 'H32', $this->hash ) );
		} else {
			$outmd5_sql = 0; // field cannot be null
			// TODO: Change Database layout to allow for null values
		}
		$out = array( 'math_inputhash' => $this->getInputHash(), 'math_outputhash' => $outmd5_sql,
				'math_html_conservativeness' => $this->conservativeness, 'math_html' => $this->html,
				'math_mathml' => utf8_encode( $this->mathml ) );
		wfDebugLog( "Math", "Store Data:" . var_export( $out, true ) . "\n\n" );
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
	 * Get the hash calculated by texvc
	 *
	 * @return string hash
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @param string $hash
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
	 * Gets the MathML XML element
	 * @return string in UTF-8 encoding
	 */
	public function getMathml() {
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
	 * @param bool $purge
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
	 *
	 * @param (MW_MATHSTYLE_INLINE_DISPLAYSTYLE|MW_MATHSTYLE_DISPLAY|MW_MATHSTYLE_INLINE) $mathStyle
	 */
	public function setMathStyle( $displayStyle = MW_MATHSTYLE_DISPLAY ) {
		if ( $this->mathStyle !== $displayStyle ){
			$this->changed = true;
		}
		$this->mathStyle = $displayStyle;
	}

	/**
	 * Returns the value of the DisplayStyle attribute
	 * @return (MW_MATHSTYLE_INLINE_DISPLAYSTYLE|MW_MATHSTYLE_DISPLAY|MW_MATHSTYLE_INLINE) the DisplayStyle
	 */
	public function getMathStyle() {
		return $this->mathStyle;
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
			if ( $checker->isValid() ) {
				$this->setTex( $checker->getValidTex() );
				$this->texSecure = true;
				return true;
			} else {
				$this->lastError = $checker->getError();
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * @return string original tex string specified by the user
	 */
	public function getUserInputTex() {
		return $this->userInputTex;
	}
}

