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
	var $inputhash = '';
	var $hash = '';
	var $html = '';
	var $mathml = '';
	var $conservativeness = 0;
	var $params = '';
	//DEBUG variables
	protected $log='';
	protected $status_code='';
	protected $valid_xml='';
	private $success=false;
	protected $timestamp;
	protected $recall;
	protected $anchorID = 0;

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
		global $wgDefaultUserOptions;
		$validModes = array( MW_MATH_PNG, MW_MATH_SOURCE, MW_MATH_MATHJAX );
		if ( !in_array( $mode, $validModes ) )
			$mode = $wgDefaultUserOptions['math'];
		switch ( $mode ) {
			case MW_MATH_SOURCE:
				$renderer = new MathSource( $tex, $params );
				break;
			case MW_MATH_MATHJAX:
				$renderer = new MathMathJax( $tex, $params );
				break;
			case MW_MATH_PNG:
			default:
				$renderer = new MathTexvc( $tex, $params );
		}
		wfDebugLog( "Math", 'start rendering $' . $renderer->tex . '$' );
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
		$mf = wfMessage( 'math_failure' )->inContentLanguage()->escaped();
		$errmsg = wfMessage( $msg )->inContentLanguage()->escaped();
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return "<strong class='error'>$mf ($errmsg$append): $source</strong>\n";
	}

	/**
	 * Return hash of input
	 *
	 * @return string hash
	 */
	public function getInputHash() {
		// TODO: What happens if $tex is empty?
		if ($this->inputhash==''){
			$dbr = wfGetDB( DB_SLAVE );
			return $dbr->encodeBlob( pack( "H32", md5( $this->tex ) ) ); # Binary packed, not hex
		} else {
			return $this->inputhash;
		}
	}
	public function initializeFromDBRow($rpage){
		global $wgDebugMath;
		$dbr = wfGetDB( DB_SLAVE );
		$xhash = unpack( 'H32md5', $dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
		$this->hash = $xhash['md5'];
		$this->conservativeness = $rpage->math_html_conservativeness;
		$this->html = $rpage->math_html;
		$this->mathml =utf8_decode( $rpage->math_mathml);
		$this->recall = true;
		if($wgDebugMath){
			$dbtex=$rpage->math_tex;
			if ( $dbtex != $this->tex ) {
				if ( $this->tex != "" ) {
					wfDebugLog("Math","WARNING database text is $dbtex whereas"
						." input text was".$this->tex);
				} else {
					$this->tex=$dbtex;
				}
			}
			$this->status_code=$rpage->math_status;
			$this->valid_xml=$rpage->valid_xml;
			$this->log=$rpage->math_log;
			$this->timestamp=$rpage->math_timestamp;
		}
	}
	/**
	 * @return array with the database column names
	 */
	private function dbInArray(){
		global $wgDebugMath;
		$in= array(
				'math_inputhash',
				'math_outputhash'  ,
				'math_html_conservativeness' ,
				'math_html',
				'math_mathml');
		if ($wgDebugMath){
			$debug_in= array(
					'math_status' ,
					'valid_xml' ,
					'math_tex' ,
					'math_log',
					'math_timestamp');
			$in=array_merge($in,$debug_in);
		}
		return $in;
	}
	/**
	 * Reads rendering data from database
	 *
	 * @return boolean true if read successfully, false otherwise
	 */
	protected function readFromDB() {
		$dbr = wfGetDB( DB_SLAVE );
		$rpage = $dbr->selectRow(
			'math',
			$this->dbInArray(),
			array(
				'math_inputhash' => $this->getInputHash()
			),
			__METHOD__
		);
		if ( $rpage !== false ) {
			$this->initializeFromDBRow($rpage);
			return true;
		}

		# Missing from the database and/or the render cache
		wfDebugLog("Math","cachemiss");
		$this->recall = false;
		return false;
	}
	/**
	 * @return Ambigous <multitype:, multitype:unknown number string mixed >
	 */
	private function dbOutArray(){
		global $wgDebugMath;
		$dbr = wfGetDB( DB_SLAVE );
		if ( $this->hash )
			$outmd5_sql = $dbr->encodeBlob( pack( 'H32', $this->hash ) );
		else
			$outmd5_sql = 0; //field cannot be null
		//TODO: Change Database layout to allow for null values
		$out= array(
				'math_inputhash' => $this->getInputHash(),
				'math_outputhash' => $outmd5_sql ,
				'math_html_conservativeness' => $this->conservativeness,
				'math_html' => $this->html,
				'math_mathml' => utf8_encode($this->mathml));
		if ($wgDebugMath){
			$debug_out= array(
					'math_status' => $this->status_code,
					'valid_xml' => $this->valid_xml,
					'math_tex' => $this->tex,
					'math_log' => $this->log);
			$out=array_merge($out,$debug_out);
		}
		wfDebugLog("Math","Store Data:".var_export($out,true)."\n\n");
		return $out;
	}
	/**
	 * Writes rendering entry to database
	 */
	protected function writeDBEntry( $dbw=null ) {
		# Now save it back to the DB:
			if ( !wfReadOnly() ) {
			if($dbw==null){
				$dbw = wfGetDB( DB_MASTER );
			}
			wfDebugLog( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . $this->getInputHash() . ')\n' );
			$outArray=$this->dbOutArray();
			$dbw->onTransactionIdle(
				function () use ($dbw,$outArray){
					$dbw->replace('math', array( 'math_inputhash' ), $outArray, __METHOD__);
					}
			);
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
		$attribs = Sanitizer::validateTagAttributes( $this->params, $tag );
		$attribs = Sanitizer::mergeAttributes( $defaults, $attribs );
		$attribs = Sanitizer::mergeAttributes( $attribs, $overrides );
		return $attribs;
	}
	/**
	 * Writes cache.  Does nothing by default
	 */
	public function writeCache() {
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
	 * returns true if the rendering was successful
	 * @return boolean
	 */
	public function isSuccess() {
		return $this->success;
	}
	/**
	 *
	 */
	public function setSuccess($b) {
		$this->success=$b;
	}
	/**
	 * Gets anchor ID
	 *
	 * @return string anchor ID
	 */
	public function getAnchorID() {
		return $this->anchorID;
	}

	/**
	 * Sets anchor ID
	 *
	 * @param string ID anchor ID
	 */
	public function setAnchorID( $ID ) {
		$this->anchorID = $ID;
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
	 */
	public function getTimestamp(){
		return $this->timestamp;
	}
}
