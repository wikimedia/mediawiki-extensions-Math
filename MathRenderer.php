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

	//REPRESENTATIONS OF THE MATHEMATCAL CONTENT
	/** @var string tex representation */
	protected $tex = '';
	/** @var string MathML content and presentation */
	protected $mathml = '';
	/** @var string SVG layot only (no semantics)*/
	protected $svg = '';
	/** @var string the original user input string (which was used to caculate the inputhash) */
	protected $userInputTex = '';
	//FURTHER PROPERTIES OF THE MATHEMATICAL CONTENT
	/** @var (boolean|null) by default all equations are rendered in inline style (true = displaystyle) */
	protected $displaytyle = null;
	/** @var array with userdefined parameters passed to the extension (not used) */
	protected $params = array();
	/** @var string a userdefined identifyer to link to the equation. */
	protected $id = '';

	//DEBUG VARIABLES
	//Availible, if Math extension runs in debug mode ($wgMathDebug = true) only.
	/** @var int LaTeXML retun code */
	protected $statusCode = 0;
	/** @var timestamp of the last modification of the databas entry */
	protected $timestamp;
	/** @var log messages generated while conversion of mathematical contnet */
	protected $log = '';

	//STATE OF THE CLASS INSTANCE
	/** @var boolean has variable tex been security-checked */
	protected $texSecure = false;
	/** @var boolean has the mathtematical content changed */
	protected $changed = false;
	/** @var boolean is there a database entry for the mathematical contetn */
	protected $storedInDatabase = null;
	/** @var boolean is there a request to purge the existing mathematical content */
	protected $purge = false;
	/** @var string with last occured error */
	protected $lastError = '';
	/** @var string md5 value from userInputTex */
	protected $md5 = '';
	/** @var binary packed inputhash */
	protected $inputHash = '';
	/** @var int rendering mode MW_MATH_(PNG|MATHML|SOURCE...)*/
	protected $mode = MW_MATH_MATHML;

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
	public static function renderMath( $tex, $params = array(), $mode = MW_MATH_MATHML ) {
		$renderer = self::getRenderer( $tex, $params, $mode );
		if ( $renderer->render() )
			return $renderer->getHtmlOutput();
	}


	/**
	 *
	 * @param type $md5
	 * @return MathRenderer the MathRenderer generated from md5
	 */
	public static function newFromMd5($md5){
		$class = get_called_class();
		$instance = new $class;
		$instance->setMd5($md5);
		$instance->readFromDatabase();
		return $instance;
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

		$displaytyle = null;
		if ( isset($params['display']) ){
			$layoutMode = $params['display'];
			if( $layoutMode == 'block' ){
				$displaytyle = true ;
				$tex= '{\displaystyle '. $tex.'}';
			} elseif ($layoutMode == 'inline'){
				$displaytyle = false;
				$tex= '{\textstyle '. $tex.'}';
			}
		}
		$id = null;
		if ( isset($params['id']) ) {
			$id= $params['id'];
		}
		$validModes = array( MW_MATH_PNG, MW_MATH_SOURCE, MW_MATH_MATHML );
		if ( !in_array( $mode, $validModes ) )
			$mode = $wgDefaultUserOptions['math'];
		switch ( $mode ) {
			case MW_MATH_SOURCE:
			case MW_MATH_MATHJAX:
				$renderer = new MathSource( $tex, $params );
				break;
			case MW_MATH_PNG:
				$renderer = new MathTexvc( $tex, $params );
				break;
			case MW_MATH_MATHML:
			default:
				$renderer = new MathMathML( $tex, $params );
				break;
		}
		wfDebugLog ( "Math", 'start rendering $' . $renderer->tex . '$ in mode ' . $mode );
		$renderer->setDisplaytyle( $displaytyle );
		$renderer->setID( $id );
		return $renderer;
	}

	/**
	 * Performs the rendering
	 *
	 * @return boolean if rendering was successfull.
	 */
	abstract public function render();

	/**
	 * @return string Html output that is embedded in the page
	 */
	abstract public function getHtmlOutput();

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
		return "<strong class='error'>$mf($errmsg): $source</strong>\n";
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getMd5() {
		if ($this->md5){
			return $this->md5;
		} else {
			return md5( $this->userInputTex );
		}
	}

	/**
	 * set the input hash (if user input tex is not availible)
	 *
	 * @return string hash
	 */
	public function setMd5($md5) {
		$this->md5 = $md5;
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getInputHash() {
		// TODO: What happens if $tex is empty?
		if ( !$this->inputHash ){
			$dbr = wfGetDB( DB_SLAVE );
			return $dbr->encodeBlob( pack( "H32", $this->getMd5()) ); # Binary packed, not hex
		}
		return $this->inputHash;
	}

	/**
	 * Decode binary packed hash from the database to md5 of input_tex
	 * @param binary $hash
	 * @return string md5
	 */
	private static function dbHash2md5($hash){
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
		/** @var ResultWrapper asdf */
		$rpage = $dbr->selectRow( 'math',
				$this->dbInArray(),
				array( 'math_inputhash' => $this->getInputHash() ),
				__METHOD__);

		if ( $rpage !== false ) {
			$this->initializeFromDatabaseRow( $rpage );
			$this->storedInDatabase = true;
			wfProfileOut( __METHOD__ );
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
		global $wgMathDebug;
		$in = array('math_inputhash',
			'math_mathml',
			'math_inputtex',
			'math_tex',
			'math_svg'
			);
		if ( $wgMathDebug ) {
			$debug_in = array('math_status',
				'math_log',
				'math_timestamp');
			$in = array_merge ( $in, $debug_in );
		}
		return $in;
	}

	/**
	 * Reads the values from the database but does not overwrite set values with empty values
	 * @param database_row $rpage
	 */
	protected function initializeFromDatabaseRow( $rpage ) {
		global $wgMathDebug;
		$this->inputHash = $rpage->math_inputhash; //MUST NOT BE NULL
		$this->md5 = self::dbHash2md5($this->inputHash);
		if ( $rpage->math_mathml ){
			$this->mathml = utf8_decode ( $rpage->math_mathml );
	}
		if ( $rpage->math_inputtex ) { //in the current database the field is probably not set.
			$this->userInputTex = $rpage->math_inputtex;
		}
		if ( $rpage->math_tex ) {
			$this->tex = $rpage->math_tex;
		}
		if ( $rpage->math_svg ) {
			$this->svg = $rpage->math_svg;
		}
		if ( $wgMathDebug ) {
			if ( $rpage->math_status !== null){
				$this->statusCode = $rpage->math_status;
			}
			if ( $rpage->math_log ){
				$this->log = $rpage->math_log;
			}
			$this->timestamp = $rpage->math_timestamp;
			if ( $this->userInputTex ){
				if ( $this->md5 != md5( $this->getUserInputTex() )) {
						wfDebugLog ( "Math", 'Hash in the database does not match the hash of the user inputtext.');
					}
			}
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
	 * @param DatabaseBase $dbw
	 */
	public function writeToDatabase( $dbw = null ) {
		global $wgMathDebug;
		# Now save it back to the DB:
		if ( !wfReadOnly() ) {
			$dbw = $dbw ?: wfGetDB( DB_MASTER );
			wfDebugLog( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . $this->getMd5() . ")\n" );
			$outArray = $this->dbOutArray();
			$inputHash = $this->getInputHash();
			$method = __METHOD__;
			if ( $this->isInDatabase() ){
			$dbw->onTransactionIdle(
						function() use( $dbw, $outArray, $wgMathDebug, $inputHash, $method ) {
							$dbw->update( 'math', $outArray ,array( 'math_inputhash' => $inputHash ), $method );
							if ($wgMathDebug) wfDebugLog( "Math", 'Row updated after db transaction was idle: ' . var_export( $outArray , true ). " to database \n" );
					} );
			} else {
				$dbw->onTransactionIdle(
						function() use( $dbw, $outArray, $wgMathDebug, $method ) {
							$dbw->insert( 'math', $outArray, $method , array ( 'IGNORE' ) );
							if ($wgMathDebug) {
								wfDebugLog( "Math", 'Row inserted after db transaction was idle ' . var_export( $outArray , true ). " to database \n" );
								if ( $dbw->affectedRows() == 0 ){
									//That's the price for the delayed update.
									wfDebugLog( "Math", 'Entry could not be written. Might be changed in between. ' );
		}
	}
						} );
			}
		}
	}

	/**
	 * Gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	protected function dbOutArray() {
		global $wgMathDebug;
		$out = array('math_inputhash' => $this->getInputHash (),
			'math_mathml' => utf8_encode ( $this->mathml ),
			'math_inputtex'=> $this->userInputTex,
			'math_tex' => $this->tex,
			'math_svg' => $this->svg
			);
		if ( $wgMathDebug ) {
			$debug_out = array('math_status' => $this->statusCode,
				'math_log' => $this->log);
			$out = array_merge ( $out, $debug_out );
		}
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
		wfDebugLog( "Math" , "writing of cache requested." );
		if ( $this->isChanged() ) {
			wfDebugLog( "Math" , "Change detected. Perform writing." );
			$this->writeToDatabase();
		} else {
			wfDebugLog( "Math" , "Nothing was changed. Don't write to database." );
		}
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
			//wfDebugLog('Math', 'tex changed');
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
		$request = RequestContext::getMain()->getRequest();
		// TODO: Figure out if ?action=purge
		// until this issue is resolved we use ?mathpurge=true instead
		//$action = $request->getText('action'); //always returns ''
		//wfDebugLog("Math",'action = '. $action);
		$mathpurge = $request->getBool( 'mathpurge', false );
		if ( //$action == "purge" &&
				 $mathpurge ){
			wfDebugLog('Math', 'Re-Rendering on user request');
			return true;
		} else {
			return false;
	}
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
	 * @return string
	 */
	public function getLog() {
		return $this->log;
	}

	/**
	 *
	 * @param boolean $displaytyle
	 */
	public function setDisplaytyle( $displaystyle = true ){
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
		if (!$this->texSecure) {
			$checker = new MathInputCheckTexvc( $this->userInputTex );
			if ( $checker->isSecure() ){
				$this->setTex( $checker->getSecureTex() );
				$this->texSecure = true;
				return true;
			} else {
				$this->lastError = $checker->getError();
				return false;
			}
		}
	}

	/**
	 * (Moved from core)
	 * Armour rendered math against conversion.
	 * Escape special chars in parsed math text. (in most cases are img elements)
	 *
	 * @param $text String: text to armour against conversion
	 * @return String: armoured text where { and } have been converted to
	 *                 &#123; and &#125;
	 */
	public static function armourMath( $text ) {
		// convert '-{' and '}-' to '-&#123;' and '&#125;-' to prevent
		// any unwanted markup appearing in the math image tag.
		$text = strtr( $text, array( '-{' => '-&#123;', '}-' => '&#125;-' ) );
		return $text;
	}

	public function isInDatabase(){
		if ( $this->storedInDatabase === null ){
			$this->readFromDatabase();
		}
		return $this->storedInDatabase;
	}

	/**
	 *
	 * @return string TeX the original tex string specified by the user
	 */
	public function getUserInputTex(){
		return $this->userInputTex;
	}

	/**
	 * @return string Userdefined ID
	 */
	public function getID(){
		return $this->id;
	}

	/**
	 * @return string Userdefined ID
	 */
	public function setID( $id ){
		return $this->id = $id;
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
}