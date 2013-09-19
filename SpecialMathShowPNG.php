<?php

/**
 * This does not work at all.
 * I can get the svg and save it to a temporary part.
 * But how I can hook in the default SVG->PNG conversion is unknown
 * All attemps failed... 
 */
class SpecialMathShowPng extends SpecialPage {


	function __construct() {
		parent::__construct( 'MathShowPng' );
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
		global $wgMathDirectory;
		$request = $this->getRequest();
		$md5Hash =  $request->getVal('hash');
		$url = SpecialPage::getTitleFor('MathShowSvg')->getFullURL()."?hash=".$md5Hash;
		$u = new UploadFromUrl();
		$u->initialize($md5Hash, $url);
		$u->fetchFile();
		$fname =  $u->getTempPath();
		$backend = new FSFileBackend( array(
					'name'           => 'math-backend',
					'lockManager'    => 'nullLockManager',
					'containerPaths' => array( $fname ),
					'fileMode'       => 0777
				) );
		$repo = new LocalRepo(array('name'=>'temp','backend'=>$backend));
		$file = new LocalFile($fname,$repo);
		die($file->createThumb(100));

		$file->upload($md5Hash, '', '');
		echo $file->getThumbVirtualUrl();
		var_dump($f);
		$s = new SvgHandler();
		$s->getImageSize($f, $fname);
		var_export($s);
		echo SvgHandler::getGeneralLongDesc($file);


		echo $f->getSize();

		$f = new SvgHandler();
		$f->getImageSize($file, $path);
		echo $f->getDimensionsString($fname);
		//$u->getImageInfo($result);
		var_export($f);
		//echo $u->getFileSize();

		exit;
	}

	//put your code here
}

?>
