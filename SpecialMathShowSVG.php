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
class SpecialMathShowSvg extends SpecialPage {


	function __construct() {
		parent::__construct( 'MathShowSvg' );
	}
	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 */
	function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleBodyOnly(true);
		$out->setArticleRelated( false );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->addVaryHeader( array("Content-Type"=>'image/svg+xml') );
	}
	function execute( $par ) {
		global $wgDebugMath;
		$dbr = wfGetDB( DB_SLAVE );
		//$this->setHeaders('image/svg+xml');
//		$out = $this->getOutput();
		$request = $this->getRequest();
		$inputhash = $dbr->encodeBlob( pack( "H32", $request->getVal('hash') ) );
		//$inputhash = $dbr->encodeBlob( pack( "H32", md5('{\textstyle\sum_{i=1}^{\infty} \left(\frac12\right)^i=1}') ) );
		header('Content-type: image/svg+xml');
		$res=$dbr->select(
			'math',
			array( 'math_svg' , 'math_tex' , 'math_mathml' ),
			array( 'math_inputhash' => $inputhash )
			);
		if ($res && $res->numRows() == 1) {
			$row= $res->next();
			if ($row->math_svg){
				echo trim($row->math_svg);
			} else {
				if ($row->math_tex) {
					$renderer = new MathSvg($row->math_tex);
					$renderer->renderSvg();
					echo $renderer->getSvg();
				}
			if (! $row->math_mathml){
				$renderer = new MathLaTeXML($row->math_tex);
				$renderer->render();
				$renderer->writeToDatabase();
			}
			}
		} else {
			//TODO: Implement Error handling.
			echo "ERROR";
		}
		//avoid:Transaction idle or pre-commit callbacks still pending
		$dbr->commit();
		@exit;
	}

	//put your code here
}

?>
