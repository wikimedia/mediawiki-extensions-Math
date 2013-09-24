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
	/* @var string tex representation */
	protected $tex = '';
	/**
	 * @var XML MathML content and presentation
	 */
	protected $mathml = '';
	/**
	 * @var XML SVG layot only (no semantics)
	 */
	protected $svg = '';
	/**
	 * @var binary png for old broswers
	 */
	protected $png = '';

	//FURTHER PROPERTIES OF THE MATHEMATICAL CONTENT
	/**
	 * @var boolean by default all equations are rendered in inline style
	 * set to true for displaystyle
	 */
	protected $displaytyle = false;
	/**
	 * @var array with userdefined parameters passed to the extension (not used)
	 */
	protected $params = array();

	//DEBUG VARIABLES
	//Availible, if Math extension runs in debug mode ($wgDebugMath = true) only.
	/**
	 * @var int LaTeXML retun code
	 */
	protected $statusCode = 0;
	/**
	 * @var timestamp of the last modification of the databas entry
	 */
	protected $timestamp;
	/**
	 * @var string the original user input string (which was used to caculate the inputhash)
	 */
	protected $userInputTex = '';
	/**
	 * @var log messages generated while conversion of mathematical contnet
	 */
	protected $log = '';

	//STATE OF THE CLASS INSTANCE
	/**
	 * @var boolean has variable tex been security-checked
	 */
	protected $texSecure = false;
	/**
	 * @var boolean has the mathtematical content changed
	 */
	protected $changed = false;
	/**
	 * @var boolean is there a database entry for the mathematical contetn
	 */
	protected $storedInDatabase = null;
	/**
	 * @var boolean is there a request to purge the existing mathematical content
	 */
	protected $purge = false;
	/**
	 * @var string with last occured error
	 */
	protected $lastError = '';
	/**
	 * @var string md5 value from userInputTex
	 */
	protected $md5 = '';
	/* @var binary packed inputhash */
	protected $inputHash = '';
	/* @var int rendering mode MW_MATH_(PNG|MATHML|SOURCE...)*/
	protected $mode = MW_MATH_MATHML;
	/**
	 * @var type
	 * @deprecated since version 123
	 */
	protected $rpage = '';

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
		if ( $renderer->render() )
			return $renderer->getHtmlOutput();
	}

	/**
	 * Create a copy of another rendering instance
	 * @param MathRenderer $instance
	 */
	public function copyFrom(MathRenderer $instance ){
		$values = $instance->dbOutArray();
		//timestamps are generated on Database update (so copy them manually)
		$values['math_timestamp'] = $instance->timestamp;
		$this->initializeFromDatabaseRow((object)$values);
		//overwrite the stored in database value
		$this->storedInDatabase = $instance->isInDatabase();
		$this->changed = $instance->isChanged();
		$this->purge = $instance->isPurge();
		$this->texSecure = $instance->isTexSecure();
		$this->lastError = $instance->getLastError();
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
		$dbr = wfGetDB( DB_SLAVE );
		$rpage = $dbr->selectRow( 'math',
				$this->dbInArray(),
				array( 'math_inputhash' => $this->getInputHash() ),
				__METHOD__);
		if ( $rpage !== false ) {
			$this->initializeFromDatabaseRow( $rpage );
			$this->storedInDatabase = true;
			return true;
		} else {
			# Missing from the database and/or the render cache
			$this->storedInDatabase = false;
			return false;
		}
	}

	/**
	 * @return array with the database column names
	 */
	private function dbInArray() {
		global $wgMathDebug;
		$in = array('math_inputhash',
			'math_mathml',
			'math_svg',
			'math_png',
			'math_tex'
			);
		if ( $wgMathDebug ) {
			$debug_in = array('math_status',
				'math_inputtex',
				'math_log',
				'math_timestamp');
			$in = array_merge ( $in, $debug_in );
		}
		return $in;
	}

	/**
	 *
	 * @param database_row $rpage
	 */
	public function initializeFromDatabaseRow( $rpage ) {
		global $wgMathDebug;
		$this->mathml = utf8_decode ( $rpage->math_mathml );
		$this->storedInDatabase = true;
		$this->tex = $rpage->math_tex;
		$this->png = base64_decode( $rpage->math_png );
		$this->svg = $rpage->math_svg;
		$this->inputHash  = $rpage->math_inputhash;
		$this->md5 = self::dbHash2md5($this->inputHash);
		//TODO: FIX
		if ( $wgMathDebug ) {
			$this->userInputTex = $rpage->math_inputtex;
			$this->statusCode = $rpage->math_status;
			$this->log = $rpage->math_log;
			$this->timestamp = $rpage->math_timestamp;
			if ( $this->userInputTex ){
				if ( $rpage->math_inputtex != $this->userInputTex ) {
						wfDebugLog ( "Math", 'WARNING database text is '.
							var_export( $rpage->math_inputtex , true ).' whereas input text was' . $this->userInputTex );
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
	 */
	public function writeToDatabase( $dbw = null ) {
		global $wgMathDebug;
		# Now save it back to the DB:
		if ( !wfReadOnly() ) {
			$dbw = $dbw ?: wfGetDB( DB_MASTER );
			wfDebugLog( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . $this->getMd5() . ")\n" );
			$outArray = $this->dbOutArray();
			$dbw->onTransactionIdle(
					function() use( $dbw, $outArray, $wgMathDebug ) {
						$dbw->replace( 'math', array( 'math_inputhash' ), $outArray, __METHOD__ );
						if ($wgMathDebug) wfDebugLog( "Math", 'wrote: ' . var_export( $outArray , true ). " to database \n" );
					} );
		}
	}

	/**
	 * Gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	private function dbOutArray() {
		global $wgMathDebug;
		$dbr = wfGetDB ( DB_SLAVE );
		$out = array('math_inputhash' => $this->getInputHash (),
				'math_mathml' => utf8_encode ( $this->mathml ),
				'math_svg' => $this->getSvg(),
				'math_png'=> base64_encode($this->png),
				'math_tex' => $this->tex
			);
		if ( $wgMathDebug ) {
			$debug_out = array('math_status' => $this->statusCode,
				'math_inputtex'=> $this->userInputTex,
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
		$action = $request->getText('action');
		$mathpurge = $request->getBool( 'mathpurge', false );
		if ( $action == "purge" && $mathpurge ){
			wfDebugLog('Math', 'Re-Rendering on user request');
			return true;
		} else {
			return false;
		}
		// TODO: Figure out if ?action=purge
		// until this issue is resolved we use ?mathpurge=true instead
		//global $wgRequest;
		//return ( $wgRequest->getVal( 'mathpurge' ) === "true" );
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
	public function setPng( $png ){
		$this->changed = true;
		$this->png = $png;
	}
	/**
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

	private function getMathImageUrl(){
		return SpecialPage::getTitleFor('MathShowImage')->getLocalURL(array('hash'=>$this->getMd5()));
	}
	/**
	 * Gets img tag for math image
	 *
	 * @return string img HTML
	 */
	public function getMathImageHTML() {
		$url = $this->getMathImageUrl();
		$style = '';
		if ($this->getDisplaytyle()){
			$style = 'display:block;margin:auto';
		}
		return Xml::element( 'img',
			$this->getAttributes(
				'img',
				array(
					'class' => 'tex',
					'alt' => $this->getTex(),
					'style'=>$style
				),
				array(
					'src' => $url
				)
			)
		);
	}
}