<?php
/**
 * MediaWiki math extension
 *
 * (c)2012 Moritz Schubotz
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the LaTeXML daemon
 * @file
 * @ingroup Parser
 */




class MathLaTeXML extends MathRenderer {
	/* (non-PHPdoc)
	 * @see MathRenderer::render()
	*/
	function render($purge=false) {
		if ( $purge||!$this->_readFromDB() || $this->mathml == "" || $this->isPurge() ) { // ||
			wfDebugLog( "Math", "no recall" );
			$this->dorender();
		}
		return $this->_embedMathML().
		' <a href="/wiki/Spezial:MathSearch?pattern='.urlencode($this->tex).'&searchx=Search"><img src="http://arxivdemo.formulasearchengine.com/images/FSE-PIC.png" width="15" height="15"></a>'
		;
	}

	/* (non-PHPdoc)
	 * @see MathRenderer::writeCache()
	 */
	function writeCache() {
		if ( !$this->isRecall() ){
			$this->hash=0;
			$this->_writeDBentry();
		}
	}
	/**
	 * Picks a LaTeXML daemon if more than one are availible from the $wgLaTeXMLUrl array
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
	private function dorender() {

		$host = self::pickHost();
		$texcmd = "literal:" . urlencode( "\$" . $this->tex . "\$" );
		$post = "profile=fragment&tex=$texcmd";
		$time_start = microtime( true );
		$res = Http::post( $host, array( "postData" => $post, "timeout" => 60 ) );
		$time_end = microtime( true );
		$time = $time_end - $time_start;
		$result = json_decode( $res );
		if ( $result ) {// &&is_array($result)&&is_array($result['result'])&&count($result['result'])>0){
			if ( $result->status != "No obvious problems" ) {
				$this->status = $result->status;
				$this->log = $result->log;
			}
			if ( ( strpos( $result->result, '<?xml version="1.0" encoding="utf-8"?>' ) === 0 ) )
			{
				wfDebugLog( "Math", "ERROR: Result is invalid " . $result->result );
				return false;
			}
			$this->status = $result->status;
			$this->log = $result->log;
			$this->mathml = $result->result;
		}
		else {
			wfDebugLog( "Math", "\nLaTeXML Error:" . var_export( array( $result, $post, $host ), true ) . "\n\n" );
			return false;
		}
		wfDebugLog( "Math", "Latexml request: $post\n processed in $time seconds." );
		return true;
	}
	/**
	 * @return boolean
	 */
	function isPurge() {
		// TODO: Figure out if ?action=purge
		// until this issue is resolved we use ?mathpurge=true instead
		global $wgRequest;
		return ( $wgRequest->getVal( 'mathpurge' ) === "true" );
	}

	/**
	 * @return string
	 */
	private function _embedMathML() {
		return self::embedMathML($this->mathml, $this->getAnchorID());
	}
	public static function embedMathML($mml,$anchorID=0){
		$mml = str_replace( "\n", " ", $mml );
		return Xml::tags( 'span',
				self::attribs( 'span',
						array(
								'class' => 'tex',
								'dir' => 'ltr',
								'id' => 'math' . $anchorID
						)
				),
				$mml
		);
	}
	/**
	 * @param string $tag
	 * @param array $defaults
	 * @param array $overrides
	 * @return array
	 */
	protected static function attribs( $tag, $defaults = array(), $overrides = array() ) {
		$attribs = Sanitizer::validateTagAttributes( array(), $tag );
		$attribs = Sanitizer::mergeAttributes( $defaults, $attribs );
		$attribs = Sanitizer::mergeAttributes( $attribs, $overrides );
		return $attribs;
	}
}