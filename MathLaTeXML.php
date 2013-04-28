<?php
/**
 * MediaWiki math extension
 *
 * (c)2012 Moritz Schubotz
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the LaTeXML daemon
 * @file
 */




class MathLaTeXML extends MathRenderer {
	
	private $LaTeXMLSettings='';
	
	public function getLaTeXMLSettings(){
		if($this->LaTeXMLSettings){
			return $this->LaTeXMLSettings;
		} else {
			return 'format=xhtml&'.
				'whatsin=math&'.
				'whatsout=math&'.
				'pmml&'.
				'cmml&'.
				'preload=LaTeX.pool&'.
				'preload=article.cls&'.
				'preload=amsmath&'.
				'preload=amsthm&'.
				'preload=amstext&'.
				'preload=amssymb&'.
				'preload=eucal&'.
				'preload=[dvipsnames]xcolor&'.
				'preload=url&'.
				'preload=hyperref&'.
				'preload=mws&'.
				'preload=ids&'.
				'preload=texvc';
		}
	}
	
	public function setLaTeXMLSettings($sesttings){
		$this->LaTeXMLSettings=$sesttings;
	}
	/* (non-PHPdoc)
	 * @see MathRenderer::render()
	*/
	function render($purge=false) {
		$recall =false;
		if ( !$purge&& !$this->isPurge()){
			$dbres=$this->readDatabaseEntry();
			if ($dbres) {
				if (self::isValidMathML($this->getMathml())){
					$recall=true;
					$this->setSuccess(true);
				} 
			}
		}
		if (!$recall) {
			wfDebugLog( "Math", "no recall" );
			$this->recall=false;
			$success=$this->dorender();
			$this->setSuccess($success);
		}
		return $this->getEncodedMathML();
	}

	/* (non-PHPdoc)
	 * @see MathRenderer::writeCache()
	*/
	function writeCache() {
		if ( $this->wasChanged() && $this->getSuccess() ){
			$this->hash=0;
			$this->writeDatabaseEntry();
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
	private static function generalize($bad,$correct,$input){
		return str_replace($bad,$correct,str_replace($correct,$bad,$input));
	}
	/**
	 * @return boolean
	 */
	private function dorender() {
		global $wgDebugMath,$wgLaTeXMLTimeout;
		$host = self::pickHost();
		$texcmd = urlencode( $this->tex );
		$post= $this->getLaTeXMLSettings();
		$post .='&tex='.$texcmd;
		$time_start = microtime( true );
		$res = Http::post( $host, array( "postData" => $post, "timeout" => $wgLaTeXMLTimeout) );
		$time_end = microtime( true );
		$time = $time_end - $time_start;
		if($time>$wgLaTeXMLTimeout){
			$this->mathml = "[ERROR (timeout)]";
			wfDebugLog( "Math", "\nLaTeXML Timeout:" . var_export( array( $wgLaTeXMLTimeout, $post, $host ), true ) . "\n\n" );
			return false;
		}
		$result = json_decode( $res );
		if ( $result ) {// &&is_array($result)&&is_array($result['result'])&&count($result['result'])>0){
			if ($wgDebugMath or $result->status != "No obvious problems" ) {
				$this->setStatusCode($result->status_code);
				$this->setLog($result->log);
			}
			if ( ( strpos( $result->result, '<?xml version="1.0" encoding="utf-8"?>' ) === 0 ) )
			{
				wfDebugLog( "Math", "ERROR: Result is invalid " . $result->result );
				return false;
			}
			$this->setMathml( $result->result);
			$this->setValidXml(self::isValidMathML($this->getMathml()));
			if(!$this->getValidXml()){
				$this->setMathml( "[ERROR (invalid)]".$result->result);
				wfDebugLog( "Math", "\nLaTeXML Error:" . var_export( array( $result, $post, $host ), true ) . "\n\n" );
			}
		}
		else {
			if($res==false){
				wfDebugLog( "Math", "\nLaTeXML Error: no response from $host \n" );
			} else {
				wfDebugLog( "Math", "\nLaTeXML Error:" . var_export( array( $result, $post, $host ), true ) . "\n" );
			}
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
	 * Checks if the input is valid MathML,
	 * and if the root element has the name math
	 * @param string $XML
	 * @return boolean
	 */
	static public function isValidMathML($XML){
		//TODO: Check: Is simpleXML core php?
		//	Is libxml_use_internal_error permanent (side effects with other methods)?
		libxml_use_internal_errors( true );
		$xml = simplexml_load_string( $XML );
		if ( !$xml ) {
			wfDebugLog("Math", "XML validation error:\n " . var_export( $XML, true ) . "\n");
			foreach ( libxml_get_errors() as $error ){
				wfDebugLog("Math", "\t". $error->message);
			}
			libxml_clear_errors();
			return false;
		} else {
			$name= $xml->getName();
			if ( $name=="math" or $name=="table" or $name=="div" ){
				return true;
			} else {
				wfDebugLog("Math", "got wrong root element" .$name);
				return false;
			}
		}
	}

	/**
	 * internal version of @link self::embedMathML
	 * @return string
	 * @return html element with rendered math
	 */
	private function getEncodedMathML() {
		return self::embedMathML($this->mathml,urldecode($this->tex));
	}
	/**
	 * @param string $mml: the MathML string 
	 * @param string $tagId: optional tagID for references like (pagename#equation2)
	 * @return html element with rendered math
	 */
	public static function embedMathML($mml,$tagId=''){
		$mml = str_replace( "\n", " ", $mml );
		return Xml::tags( 'span',
				self::attribs( 'span',
						array(
								'class' => 'tex',
								'dir' => 'ltr',
								'id' => $tagId
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