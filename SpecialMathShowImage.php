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
	function setHeaders() {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$out->setArticleBodyOnly(true);
		$out->setArticleRelated( false );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->disable();
		$request->response()->header('Cache-Control: max-age=31556926');
		if ( $this->isPng ){
			$request->response()->header( "Content-type: image/png;" );
		} else {
		$request->response()->header( "Content-type: image/svg+xml; charset=utf-8" );
		}
	}

	function execute( $par ) {
		global $wgDebugMath;
		$dbr = wfGetDB( DB_SLAVE );
		$request = $this->getRequest();
		$inputhash = $dbr->encodeBlob( pack( "H32", $request->getVal('hash') ) );
		$this->isPng = $request->getBool('png');
		$this->setHeaders();
		//echo wfShellExec('/vagrant/shell.sh');
		//exec('rsvg-convert /home/vagrant/out.svg',$output);
		//passthru('rsvg-convert /home/vagrant/out.svg');
		//die();
		if($this->isPng){
			$dbFieldName = 'math_png';
		} else {
			$dbFieldName = 'math_svg';
		}
		$res=$dbr->select(
			'math',
			array( $dbFieldName , 'math_tex' , 'math_mathml' ),
			array( 'math_inputhash' => $inputhash )
			);
		if ($res && $res->numRows() == 1) {
			$row= $res->next();
			if ( $this->isPng ){
				$dbImage = $row->math_png;
			} else {
				$dbImage = $row->math_svg;
			}
			if ( $dbImage ){
				echo $dbImage;
			} else {
				if ($row->math_tex) {
					$renderer = new MathSvg($row->math_tex);
					$renderer->renderSvg();
					if( $this->isPng ){
						echo $renderer->getPng();
					} else {
						echo $renderer->getSvg();
					}
					$renderer->writeCache();
				}
			}
			if (! $row->math_mathml){
				$renderer = new MathMathML($row->math_tex);
				$renderer->render();
				$renderer->writeToDatabase();
			}
		} else {
			//TODO: Implement Error handling.
			//create SVG Image that contains the tex string
			echo "ERROR";
		}
		//avoid:Transaction idle or pre-commit callbacks still pending
//		$dbr->commit();
		//TODO: Find a way to use the normal SpecialPage routine without to print out the whole html stuff.
	//	@exit;
	}

}