<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * @file
 */

/**
 * This is an abstract class with static methods for rendering the <math> tags.
 * This static methods create a new istance of the extending classes and render the math tags based on the
 * mode setting of the user.
 * Furthermore this class handles the caching of the rendered output and provides debug information,
 * if run in mathdebug mode.
 *
 *
 * @author Tomasz Wegrzanowski, with additions by Brion Vibber (2003, 2004) rewritten 2012 by Moritz Schubotz
 */
abstract class MathRenderer {
	// 	The following variables should made private, as soon it can be verified that they are not
	// 	being directly accessed by other extensions.
	/**
	 * TODO: Why is this variable set by default.
	 * @var MW_MATH_...
	 */
	var $mode = MW_MATH_PNG;
	var $tex = '';
	var $inputhash = '';
	var $hash = '';
	var $html = '';
	var $mathml = '';
	var $conservativeness = 0;
	var $params = '';
	var $log='';
	var $status='';
	var $status_code='';
	var $valid_xml='';
	protected $timestamp;
	protected $recall;


	/**
	 * @param string $tex (the TeX content of the <math>-tag)
	 * @param array $params (an arry of parameters, due to compatiblity reasons?, never used?)
	 */
	public function __construct( $tex='', $params = array() ) {
		$this->tex = $tex;
		$this->params = $params;
	}
	/**
	 * @param string $tex
	 * @param array $params
	 * @param int $mode: constant indicating rendering mode
	 */
	public static function renderMath( $tex, $params = array(),  $mode = MW_MATH_PNG ) {
		$renderer = self::getRenderer( $tex, $params, $mode );
		return $renderer->render();
	}
	/**
	 * @param string $tex
	 * @param unknown $params
	 * @param int $mode: constant indicating rendering mode
	 * @return MathRenderer
	 */
	public static function getRenderer( $tex, $params = array(),  $mode = MW_MATH_PNG ) {
		global $wgDefaultUserOptions;
		$validModes = array( MW_MATH_PNG, MW_MATH_SOURCE, MW_MATH_MATHJAX, MW_MATH_LATEXML );
		if ( !in_array( $mode, $validModes ) )
			$mode = $wgDefaultUserOptions['math'];
		switch ( $mode ) {
			case MW_MATH_SOURCE:
				$renderer = new MathSource( $tex, $params );
				break;
			case MW_MATH_MATHJAX:
				$renderer = new MathMathJax( $tex, $params );
				break;
			case MW_MATH_LATEXML:
				$renderer = new MathLaTeXML( $tex, $params );
				break;
			case MW_MATH_PNG:
			default:
				$renderer = new MathLaTeXMLImages( $tex, $params );
		}

		return $renderer;
	}


	/**
	 * Performs the rendering and returns the rendered element that needs to be embedded.
	 * @return string:html element with rendered math
	 */
	abstract public function render($purge=false);


	/**
	 * Artefact from the texvc error messages
	 * TODO: update to MathML
	 * @param unknown $msg
	 * @param string $append
	 * @return string
	*/
	protected function error( $msg, $append = '' ) {
		$mf = wfMessage( 'math_failure' )->inContentLanguage()->escaped();
		$errmsg = wfMessage( $msg )->inContentLanguage()->escaped();
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return "<strong class='error'>$mf ($errmsg$append): $source</strong>\n";
	}


	/**
	 * @return mixed
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

	/**
	 * @return string
	 */
	public function getMd5() {
		return  md5( $this->tex ) ; # Binary packed, not hex
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
			$this->tex=$rpage->math_tex;
			$this->status_code=$rpage->math_status;
			$this->valid_xml=$rpage->valid_xml;
			$this->log=$rpage->math_log;
			$this->timestamp=$rpage->math_timestamp;
		}
	}
	/**
	 * Tries to read from the DB cache, and initilizies the fields of this class according to the stored entries
	 * @return boolean: true if the entry was found in the database.
	 */
	protected function readFromDB() {
		global $wgDebugMath;
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
		$this->recall = false;
		return false;
	}
	/**
	 *
	 */
	protected function writeDBentry() {
		# Now save it back to the DB:
		if ( !wfReadOnly() ) {
			$dbw = wfGetDB( DB_MASTER );
			wfDebugLog( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . $this->getInputHash() . ')\n' );
			$dbw->replace(
					'math',
					array( 'math_inputhash' ),
					$this->dbOutArray(),
					__METHOD__
			);
		}
	}

	/**
	 * @param string $tag
	 * @param array $defaults
	 * @param array $overrides
	 * @return array
	 */
	protected function getAttribs( $tag, $defaults = array(), $overrides = array() ) {
		$attribs = Sanitizer::validateTagAttributes( $this->params, $tag );
		$attribs = Sanitizer::mergeAttributes( $defaults, $attribs );
		$attribs = Sanitizer::mergeAttributes( $attribs, $overrides );
		return $attribs;
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
					'math_log' => $this->status."\n".$this->log);
			$out=array_merge($out,$debug_out);
		}
		wfDebugLog("Math","storeVAL:".var_export(utf8_encode ( $this->mathml),true)."ENDStoreVAL");
		return $out;
	}
	/**
	 * @return Ambigous <multitype:, multitype:unknown number string mixed >
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
	 * Does nothing by default
	 */
	public function writeCache() {
	}
	/**
	 * @return boolean
	 */
	public function isRecall() {
		return $this->recall;
	}
	/**
	 * returns the TeX code of the <math>-tag
	 * @return string
	 */
	public function getTex() {
		return $this->tex;
	}
}
