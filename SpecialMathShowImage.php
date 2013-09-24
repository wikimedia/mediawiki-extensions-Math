<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SpecialMathShowSVG
 *
 * @author ms
 */
class SpecialMathShowImage extends SpecialPage {
	private $isPng = false;

	function __construct() {
		parent::__construct( 'MathShowImage' );
	}
	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 */
	function setHeaders($chache = true) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$out->setArticleBodyOnly(true);
		$out->setArticleRelated( false );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->disable();
		if ( $chache ){
			if ( $this->isPng ){
				$request->response()->header( "Content-type: image/png;" );
			} else {
				$request->response()->header( "Content-type: image/svg+xml; charset=utf-8" );
			}
			$request->response()->header('Cache-Control: public max-age=31556926');

		} else {
			$request->response()->header( "Content-type: image/svg+xml; charset=utf-8" );
		}
	}

	function execute( $par ) {
		global $wgMathDebug;
		$request = $this->getRequest();
		$output = '';
		$renderer = MathMathML::newFromMd5($request->getVal('hash'));
		$this->isPng = $request->getBool('png');
		$success = $renderer->render();
		if ( $success ){
			if ( $this->isPng ){
				$output = base64_decode( $renderer->getPng() );
			} else {
				$output =  $renderer->getSvg();
			}
		} else {
			//Errormessage in PNG not supported
			$output = self::printSvgError($renderer->getLastError());
		}
		$this->setHeaders($success);
		echo $output;
		if ( $wgMathDebug ){
			echo '<!--'.  var_export($renderer,true).'-->';
		}
		$renderer->writeToDatabase();
	}

	/**
	 * Prints the specified error message as svg.
	 * @param string $msg error message
	 * @return xml svg image with the error message
	 */
	public static function printSvgError($msg){
		return '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'.
				'<text x="10" y="10" fill="red" >'. $msg .'</text></svg>';
	}

}