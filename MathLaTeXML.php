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

	/**
	 * Gets the setting for the LaTeXML deamon.
	 *
	 * @return string
	 */
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

	/**
	 * Sets the setting for the LaTeXML deamon.
	 * The settings affect only the current instance of the class.
	 * For a list of possible settings see:
	 * http://dlmf.nist.gov/LaTeXML/manual/commands/latexmlpost.xhtml
	 * @param string $settings
	 */
	public function setLaTeXMLSettings($settings){
		$this->LaTeXMLSettings=$settings;
	}
	/* (non-PHPdoc)
	 * @see MathRenderer::render()
	*/
	function render($purge=false) {
		$recall =false;
		if ( !$purge&& !$this->isPurge()){
			$dbres=$this->readFromDatabase();
			if ($dbres) {
				if (self::isValidMathML($this->getMathml())){
					$recall=true;
				}
			}
		}
		if (!$recall) {
			wfDebugLog( "Math", "no recall" );
			$this->recall=false;
			$success=$this->dorender();
		}
		return $this->getEncodedMathML();
	}

	/* (non-PHPdoc)
	 * @see MathRenderer::writeCache()
	*/
	function writeCache() {
		if ( $this->isChanged() ){
			$this->writeToDatabase();
		}
	}

	/**
	 * Picks a LaTeXML daemon.
	 * If more than one demon are availible one is chosen from the
	 * $wgLaTeXMLUrl array.
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
	 * Helper function to repair bugs on the fly.
	 * @param string $bad
	 * @param string $correct
	 * @param string $input
	 * @return string
	 */
	private static function generalize($bad,$correct,$input){
		return str_replace($bad,$correct,str_replace($correct,$bad,$input));
	}

	/**
	 * @return boolean
	 */
	private function dorender() {
		global $wgLaTeXMLTimeout;
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
		if ( $result ) {
			if ( ( strpos( $result->result, '<?xml version="1.0" encoding="utf-8"?>' ) === 0 ) )
			{
				wfDebugLog( "Math", "ERROR: Result is invalid " . $result->result );
				return false;
			}
			$this->setMathml( $result->result);
			if(!self::isValidMathML($this->getMathml())){
				$this->setMathml( "[ERROR (invalid)]".$this->getMathml());
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
	 * Checks if there is an explicit user request to rerender the math-tag.
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
	 * Internal version of @link self::embedMathML
	 * @return string
	 * @return html element with rendered math
	 */
	private function getEncodedMathML() {
		return self::embedMathML($this->mathml,urldecode($this->tex));
	}

	/**
	 * Embedds the MathML-XML element in a HTML span element with class tex
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
	 * Generic function for handling element attributes
	 *
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