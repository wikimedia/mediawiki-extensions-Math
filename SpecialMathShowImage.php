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
	private $noRender = false;

	function __construct() {
		parent::__construct( 'MathShowImage' );
	}
	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 */
	function setHeaders($success = true) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$out->setArticleBodyOnly(true);
		$out->setArticleRelated( false );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->disable();
		if ( $success ){
			if ( $this->isPng && $success){
				$request->response()->header( "Content-type: image/png;" );
			} else {
				$request->response()->header( "Content-type: image/svg+xml; charset=utf-8" );
			}
		}
		if ( $success && !($this->noRender) ) {
			$request->response()->header('Cache-Control: public max-age=31556926');
			$request->response()->header('Vary: User-Agent');
		}
	}

	function execute( $par ) {
		global $wgMathDebug;
		$request = $this->getRequest();
		$output = '';
		$hash = $request->getText('hash','');
		if ( !$hash ){
			$this->setHeaders(false);
			die ( $this->printSvgError('No Inputhash specified'));
		}
		$renderer = MathMathML::newFromMd5( $hash );
		$this->isPng = $request->getBool('png');
		$this->noRender = $request->getBool('noRender',false);
		if ($this->noRender){
			$success = $renderer->readFromDatabase();
		} else {
			$success = $renderer->render();
		}
		if ( $success ){
			if ( $this->isPng ){
				$output = $renderer->getPng();
			} else {
				$output =  $renderer->getSvg();
			}
		} else {
			//Errormessage in PNG not supported
			$output = self::printSvgError($renderer->getLastError());
		}
		if ( $output == "" ){
			$output = $this->printSvgError('No Output produced');
			$success = false;
		}
		$this->setHeaders( $success );
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
				'<text x="20" y="20" fill="red" >'. $msg .'</text></svg>';
	}

}