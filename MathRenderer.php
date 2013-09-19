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
	/**
	 * is calculated by texvc.
	 * @var string
	 */
	protected $mathml = '';
	protected $svg = '';
	protected $params = '';
	protected $changed = false;
	/**
	 * @var boolean forces rerendering if set to true
	 */
	protected $purge = false;
	protected $recall;
	protected $lastError = '';
	protected $log = '';
	protected $storedInDatabase = false;
	protected $statusCode = 0;
	protected $timestamp;
	protected $texSecure = false;
	protected $userInputTex = '';
	protected $png = '';
	/**
	 *
	 * @var boolean by default all equations are rendered in inline style
	 * set to true for displaystyle
	 */
	protected $displaytyle = false;

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
	 *
	 * @param MathRenderer $instance
	 */
	public function copyFrom(MathRenderer $instance ){
		$values = $instance->dbOutArray();
		//timestamps are generated on Database update (so copy them manually)
		$values['math_timestamp'] = $instance->timestamp;
		$this->initializeFromDatabaseRow((object)$values);
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
		global $wgDefaultUserOptions;

		$displaytyle = false;
		if ( isset($params['display']) ){
			$layoutMode = $params['display'];
			if( $layoutMode == 'block' ){
				$displaytyle = true ;
				$tex= '{\displaystyle'. $tex.'}';
			} elseif ($layoutMode == 'inline'){
				$displaytyle = false;
				$tex= '{\textstyle'. $tex.'}';
			}
		}

		$validModes = array( MW_MATH_SVG, MW_MATH_SOURCE, MW_MATH_MATHML );
		if ( !in_array( $mode, $validModes ) )
			$mode = $wgDefaultUserOptions['math'];
		switch ( $mode ) {
			case MW_MATH_SOURCE:
			case MW_MATH_MATHJAX:
				$renderer = new MathSource( $tex, $params );
				break;
			case MW_MATH_MATHML:
				$renderer = new MathMathML( $tex, $params );
				$svg = new MathSvg();
				$svg->copyFrom($renderer);
				break;
			case MW_MATH_PNG:
			default:
				$renderer = new MathSvg( $tex, $params );
		}
		wfDebugLog ( "Math", 'start rendering $' . $renderer->tex . '$ in mode ' . $mode );
		$renderer->setDisplaytyle( $displaytyle );
		return $renderer;
	}

	/**
	 * Performs the rendering and returns the rendered element that needs to be embedded.
	 *
	 * @return string of rendered HTML
	 */
	abstract public function render( );


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
		return "<strong class='error'>$mf ($errmsg): $source</strong>\n";
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getMd5() {
		return md5( $this->userInputTex );
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getInputHash() {
		// TODO: What happens if $tex is empty?
		$dbr = wfGetDB( DB_SLAVE );
		return $dbr->encodeBlob( pack( "H32", $this->getMd5()) ); # Binary packed, not hex
	}

	/**
	 * Reads rendering data from database
	 *
	 * @return boolean true if read successfully, false otherwise
	 */
	public function readFromDatabase() {
		$dbr = wfGetDB( DB_SLAVE );
		$rpage = $dbr->selectRow( 'math',
				$this->dbInArray(),
				array( 'math_inputhash' => $this->getInputHash() ),
				__METHOD__);
		if ( $rpage !== false ) {
			$this->initializeFromDatabaseRow( $rpage );
			$this->recall = true;
			return true;
		}

		# Missing from the database and/or the render cache
		$this->recall = false;
		return false;
	}


	/**
	 * @return array with the database column names
	 */
	private function dbInArray() {
		global $wgDebugMath;
		$in = array('math_inputhash',
			'math_mathml',
			'math_svg',
			'math_png',
			'math_inputtex');
		if ( $wgDebugMath ) {
			$debug_in = array('math_status', 'math_tex', 'math_log', 'math_timestamp');
			$in = array_merge ( $in, $debug_in );
		}
		return $in;
	}

	/**
	 *
	 * @param database_row $rpage
	 */
	public function initializeFromDatabaseRow( $rpage ) {
		global $wgDebugMath;
		$dbr = wfGetDB ( DB_SLAVE );
		$this->mathml = utf8_decode ( $rpage->math_mathml );
		$this->storedInDatabase = true;
		if ( $this->userInputTex ){
			if ( $rpage->math_inputtex != $this->tex ) {
					wfDebugLog ( "Math", 'WARNING database text is '.
						var_export( $rpage->math_inputtex , true ).' whereas input text was' . $this->userInputTex );
				}
		}
		$this->userInputTex = $rpage->math_inputtex;
		$this->png = $rpage->math_png;
		if ( $wgDebugMath ) {
			$this->tex = $rpage->math_tex;
			$this->statusCode = $rpage->math_status;
			$this->log = $rpage->math_log;
			$this->timestamp = $rpage->math_timestamp;
		}
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
		if ( !wfReadOnly () ) {
			if ( $dbw == null ) {
				$dbw = wfGetDB ( DB_MASTER );
			}
			wfDebugLog ( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . bin2hex($this->getInputHash()) . ')\n' );
			$outArray = $this->dbOutArray();
			$dbw->onTransactionIdle (
					function () use ($dbw, $outArray) {
						$dbw->replace ( 'math', array('math_inputhash'), $outArray, __METHOD__ );
					} );
		}
		$s = new SpecialExport();
	}

	/**
	 * Gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	private function dbOutArray() {
		global $wgDebugMath;
		$dbr = wfGetDB ( DB_SLAVE );
		$out = array('math_inputhash' => $this->getInputHash (),
				'math_mathml' => utf8_encode ( $this->mathml ),
				'math_svg' => $this->getSvg(),
				'math_png'=> $this->png,
				'math_inputtex'=> $this->userInputTex
			);
		if ( $wgDebugMath ) {
			$debug_out = array('math_status' => $this->statusCode,
				'math_tex' => $this->tex,
				'math_log' => $this->log);
			$out = array_merge ( $out, $debug_out );
		}
		wfDebugLog ( "Math", "Store Data:" . var_export ( $out, true ) . "\n\n" );
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
	 * Writes cache.  Writes the database entry if values were changed
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
	 * get the timestamp, of the last rending of that equation
	 * @return int
	 */
	public function getTimestamp() {
		return $this->timestamp;
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
		if( $this->tex != $tex){
			$this->changed = true;
			$this->tex = $tex;
		}
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
			//If we can not check if mathml output is valid, we skip the test and assume that it is valid.
		} elseif( ! StringUtils::isUtf8( $this->mathml ) ) {
			$this->setMathml('');
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

	function getLastError(){
		return $this->lastError;
	}

	/**
	 * @return string
	 */
	public function getLog() {
		return $this->log;
	}
	/**
	 *
	 * @param boolean $displaytyle
	 */
	public function setDisplaytyle( $displaystyle= true ){
		$this->changed = true; //Discuss if this is a change
		$this->displaytyle = $displaystyle;
	}
	/**
	 *
	 * @param boolean $displaytyle
	 */
	public function getDisplaytyle(){
		return $this->displaytyle;
	}

	/**
	 * @param string $log
	 */
	public function setLog( $log ) {
		$this->changed = true;
		$this->log = $log;
	}

	/**
	 * @param int $timestamp
	 */
	public function setTimestamp( $timestamp ) {
		$this->changed = true;
		$this->timestamp = $timestamp;
	}

	/**
	 * @return int
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * @param unknown_type $statusCode
	 */
	public function setStatusCode( $statusCode ) {
		$this->changed = true;
		$this->statusCode = $statusCode;
	}

	/**
	 * Get if the input tex was marked as secure
	 * @return boolean
	 */
	public function isTexSecure (){
		return $this->texSecure;
	}

	public function checkTex(){
		$checker = new MathTexvcInputCheck( $this->tex );
		if ( $checker->isSecure() ){
			$this->setTex( $checker->getSecureTex() );
			$this->texSecure = true;
			return true;
		} else {
			$this->lastError = $checker->getError();
			return false;
		}
	}

	/**
	 *
	 * @param type $svg
	 */
	public function setSvg($svg){
		$this->changed = true;
		$this->svg = trim($svg);
	}

	/**
	 *
	 * @return type
	 */
	public function getSvg(){
		//Spaces will prevent the image from beeing displayed correctly in the browser
		return trim($this->svg);
	}

	/**
	 *
	 * @return binary picture
	 */
	public function getPng(){
		return $this->png;
	}

	/**
	 *
	 * @param binary $png
	 */
	public function setPng($png){
		$this->changed = true;
		$this->png = $png;
	}
}