<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the texvc program
 * @file
 * @ingroup Parser
 */
/**
 * Takes LaTeX fragments, sends them to a helper program (texvc) for rendering
 * to rasterized PNG and HTML and MathML approximations. An appropriate
 * rendering form is picked and returned.
 *
 * @author Tomasz Wegrzanowski, with additions by Brion Vibber (2003, 2004)
 * @ingroup Parser
 */
define( 'MW_TEXVC_SUCCESS', -1 );
class MathLaTeXMLImages extends MathRenderer {

	function render($purge=false) {
		if ( trim( $this->tex ) == '' ) {
			return; # bug 8372
		}
		if (!$this->recall() ) { // cache miss
			wfDebugLog( 'Math', "cache miss" );
			$result = $this->callTexvc();
			if ( $result != MW_TEXVC_SUCCESS )
				return $result;
		}
		return $this->doRender();
	}

	/**
	 * @return string Storage directory
	 */
	function getHashPath() {
		$path = $this->getBackend()->getRootStoragePath() .
		'/math-render/' . $this->getHashSubPath();
		wfDebug( "TeX: getHashPath, hash is: {$this->getMd5()}, path is: $path\n" );
		return $path;
	}

	/**
	 * @return string Relative directory
	 */
	function getHashSubPath() {
		return substr( $this->getMd5(), 0, 1 )
		. '/' . substr( $this->getMd5(), 1, 1 )
		. '/' . substr( $this->getMd5(), 2, 1 );
	}

	function mathImageUrl() {
		global $wgMathPath;
		$dir = $this->getHashSubPath();
		return "$wgMathPath/$dir/{$this->getMd5()}.png";
	}
	function linkToMathImage() {
		$url = $this->mathImageUrl();

		return Xml::element( 'img',
				$this->getAttributes(
						'img',
						array(
								'class' => 'tex',
								'alt' => $this->tex
						),
						array(
								'src' => $url
						)
				)
		);
	}
	private function getMd5(){
		return md5( $this->tex );
	}
	function callTexvc() {
		$tmpDir = wfTempDir();
		global $wgTmpDirectory;
		global $wgLaTeXML, $wgTexvcBackgroundColor, $wgUseSquid;
		if ( !is_executable( $wgLaTeXML ) ) {
			wfDebugLog("Math", "$wgLaTeXML seems to be no executable");
			return $this->getError( 'math_notexvc' );
		}
		$cmd = $wgLaTeXML . ' --preload=amsmath  --preload=amssymb'.
				' --preload=amsfonts --preload=cancel --preload=color --preload=upgreek --verbose'.
				' --mathimage=' .	wfEscapeShellArg( $tmpDir.'/'.$this->getMd5().'.png' ) .
				' --pmml='. 	wfEscapeShellArg( $tmpDir.'/'.$this->getMd5().'.pmml' ) .
				' --cmml='. 	wfEscapeShellArg( $tmpDir.'/'.$this->getMd5().'.cmml' ) .
				' -- ' .wfEscapeShellArg( str_replace('', '', $this->tex) ). ' 2>'
						.wfEscapeShellArg( $tmpDir.'/'.$this->getMd5().'.log' );
		//TODO: CHECK IF encoding and background matter
		//wfEscapeShellArg( 'UTF-8' ) . ' ' .
		//wfEscapeShellArg( $wgTexvcBackgroundColor );

		if ( wfIsWindows() ) {
			# Invoke it within cygwin sh, because texvc expects sh features in its default shell
			$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
		}

		wfDebugLog( 'Math', "TeX: $cmd\n" );
		$contents = wfShellExec( $cmd);
		wfDebugLog( 'Math', "TeX output:\n $contents\n---\n" );

		$tempFsFile = new TempFSFile( "$tmpDir/{$this->hash}.png" );
		$tempFsFile->autocollect(); // destroy file when $tempFsFile leaves scope

		$hashpath = $this->getHashPath(); // final storage directory

		$backend = $this->getBackend();
		# Create any containers/directories as needed...
		if ( !$backend->prepare( array( 'dir' => $hashpath ) )->isOK() ) {
			return $this->getError( 'math_output_error' );
		}
		// Store the file at the final storage path...
		$fResult= $backend->quickStore( array(
				'src' => "$tmpDir/{$this->getMd5()}.png", 'dst' => "$hashpath/{$this->getMd5()}.png"
		) );

		if ( !$fResult->isOK()
		) {
			wfDebugLog("Math", 'error moving file:'.var_export($fResult,true));
			return $this->getError( 'math_output_error' );
		}
		return MW_TEXVC_SUCCESS;

	}
	/**
	 * @return FileBackend protected
	 */
	function getBackend() {
		global $wgMathFileBackend, $wgMathDirectory;

		if ( $wgMathFileBackend ) {
			return FileBackendGroup::singleton()->get( $wgMathFileBackend );
		} else {
			static $backend = null;
			if ( !$backend ) {
				$backend = new FSFileBackend( array(
						'name'           => 'math-backend',
						'lockManager'    => 'nullLockManager',
						'containerPaths' => array( 'math-render' => $wgMathDirectory ),
						'fileMode'       => 0777
				) );
			}
			return $backend;
		}
	}
	function doRender() {
		if ( $this->mode == MW_MATH_MATHML && $this->mathml != '' ) {
			return Xml::tags( 'math',
					$this->getAttributes( 'math',
							array( 'xmlns' => 'http://www.w3.org/1998/Math/MathML' ) ),
					$this->mathml );
		}
		if ( ( $this->mode == MW_MATH_PNG ) || ( $this->html == '' ) ||
				( ( $this->mode == MW_MATH_SIMPLE ) && ( $this->conservativeness != 2 ) ) ||
				( ( $this->mode == MW_MATH_MODERN || $this->mode == MW_MATH_MATHML ) && ( $this->conservativeness == 0 ) )
		)
		{
			return $this->linkToMathImage();
		} else {
			return Xml::tags( 'span',
					$this->getAttributes( 'span',
							array( 'class' => 'texhtml',
									'dir' => 'ltr'
							) ),
					$this->html
			);
		}
	}
	public function writeCache() {
		global $wgUseSquid;
		if ( !$this->isRecall() ) {
			return;
		}
		//$this->writeDBentry();
		// If we're replacing an older version of the image, make sure it's current.
		if ( $wgUseSquid ) {
			$urls = array( $this->mathImageUrl() );
			$u = new SquidUpdate( $urls );
			$u->doUpdate();
		}
	}
	function recall() {
		/*global $wgMathCheckFiles;
		 if ( $this->readFromDB() ) {
		if ( !$wgMathCheckFiles ) {
		// Short-circuit the file existence & migration checks
		return true;
		}*/
		if($this->isPurge()){
			return false;
		}
		$filename = $this->getHashPath() . "/{$this->getMd5()}.png"; // final storage path
		$backend = $this->getBackend();
		if ( $backend->fileExists( array( 'src' => $filename ) ) ) {
			if ( $backend->getFileSize( array( 'src' => $filename ) ) == 0 ) {
				// Some horrible error corrupted stuff :(
				$backend->quickDelete( array( 'src' => $filename ) );
			} else {
				return true; // cache hit
			}
			//}
		}
		return false;
	}

}