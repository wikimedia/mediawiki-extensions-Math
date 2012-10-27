<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains everything related to <math> </math> parsing
 * @file
 */

/**
 * Takes LaTeX fragments, sends them to a helper program (texvc) for rendering
 * to rasterized PNG and HTML and MathML approximations. An appropriate
 * rendering form is picked and returned.
 *
 * @author Tomasz Wegrzanowski, with additions by Brion Vibber (2003, 2004) rewritten 2012 by Moritz Schubotz
 */
abstract class MathRenderer {
	/**
	 *  The following variables should made private, as soon it can be verified that they are not being directly accessed by other extensions.
	 */
	var $mode = MW_MATH_PNG;
	var $tex = '';
	var $inputhash = '';
	var $hash = '';
	var $html = '';
	var $mathml = '';
	var $conservativeness = 0;
	var $params = '';
	protected $recall;
	protected $anchorID = 0;

	/**
	 * @param string $tex
	 * @param array $params
	 */
	public function __construct( $tex, $params = array() ) {
		$this->tex = $tex;
		$this->params = $params;
	}

	/**
	 * @param string $tex
	 * @param unknown $params
	 * @param string $mode
	 */
	public static function renderMath( $tex, $params = array(),  $mode = MW_MATH_PNG ) {
		$renderer = getRenderer( $tex, $params, $mode );
		return $renderer->render();
	}

	/**
	 * @param string $tex
	 * @param unknown $params
	 * @param Maths constants $mode
	 * @return MathRenderer
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
		return $renderer;
	}

	/**
	 * @return misc
	 */
	abstract public function render();

	/**
	 * @param unknown $msg
	 * @param string $append
	 * @return string
	 */
	protected function _error( $msg, $append = '' ) {
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
		$dbr = wfGetDB( DB_SLAVE );
		return $dbr->encodeBlob( pack( "H32", md5( $this->tex ) ) ); # Binary packed, not hex
	}

	/**
	 * @return boolean
	 */
	protected function _readFromDB() {
		$dbr = wfGetDB( DB_SLAVE );
		$rpage = $dbr->selectRow(
			'math',
			array(
				'math_outputhash', 'math_html_conservativeness', 'math_html',
				'math_mathml'
			),
			array(
				'math_inputhash' => $this->getInputHash()
			),
			__METHOD__
		);
		if ( $rpage !== false ) {
			# Trailing 0x20s can get dropped by the database, add it back on if necessary:
			$xhash = unpack( 'H32md5', $dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
			$this->hash = $xhash['md5'];
			$this->conservativeness = $rpage->math_html_conservativeness;
			$this->html = $rpage->math_html;
			$this->mathml = $rpage->math_mathml;
			$this->recall = true;
			return true;
		}

		# Missing from the database and/or the render cache
		$this->recall = false;
		return false;
	}

	/**
	 *
	 */
	protected function _writeDBentry() {
		# Now save it back to the DB:
		if ( !wfReadOnly() ) {
			$dbw = wfGetDB( DB_MASTER );
			if ( $this->hash ) {
				$outmd5_sql = $dbw->encodeBlob( pack( 'H32', $this->hash ) );
			} else {
				$outmd5_sql = null;
			}
			wfDebugLog( "Math", 'store entry for $' . $this->tex . '$ in database (hash:' . $this->getInputHash() . ')\n' );
			$dbw->replace(
				'math',
				array( 'math_inputhash' ),
				array(
					'math_inputhash' => $this->getInputHash(),
					'math_outputhash' => $outmd5_sql ,
					'math_html_conservativeness' => $this->conservativeness,
					'math_html' => $this->html,
					'math_mathml' => $this->mathml,
					),
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
	protected function _attribs( $tag, $defaults = array(), $overrides = array() ) {
		$attribs = Sanitizer::validateTagAttributes( $this->params, $tag );
		$attribs = Sanitizer::mergeAttributes( $defaults, $attribs );
		$attribs = Sanitizer::mergeAttributes( $attribs, $overrides );
		return $attribs;
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
	public function getAnchorID() {
		return $this->anchorID;
	}
	public function setAnchorID( $ID ) {
		$this->anchorID = $ID;
	}
	public function getTex() {
		return $this->tex;
	}
}
