<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the texvc program
 * @file
 */
/**
 * Takes LaTeX fragments, sends them to a helper program (texvc) for rendering
 * to rasterized PNG and HTML and MathML approximations. An appropriate
 * rendering form is picked and returned.
 *
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
 * @author Moritz Schubotz
 */
class MathTexvc extends MathRenderer {
	const MW_TEXVC_SUCCESS = -1;

	/**
	 * Renders TeX using texvc
	 *
	 * @return string rendered TeK
	 */
	public function render() {
		if ( !$this->readCache() ) { // cache miss
			wfDebug('Math', 'cache miss. texvc renderer called');
			$result = $this->callTexvc();
			if ( $result != self::MW_TEXVC_SUCCESS ) {
				return $result;
			}
		}
		return $this->doHTMLRender();
	}

	/**
	 * Gets path to store hashes in
	 *
	 * @return string Storage directory
	 */
	public function getHashPath() {
		$path = $this->getBackend()->getRootStoragePath() .
			'/math-render/' . $this->getHashSubPath();
		wfDebugLog("Math", "TeX: getHashPath, hash is: {$this->getHash()}, path is: $path\n" );
		return $path;
	}

	/**
	 * Gets relative directory for this specific hash
	 *
	 * @return string Relative directory
	 */
	public function getHashSubPath() {
		return substr( $this->getHash(), 0, 1 )
			. '/' . substr( $this->getHash(), 1, 1 )
			. '/' . substr( $this->getHash(), 2, 1 );
	}

	/**
	 * Gets URL for math image
	 *
	 * @return string image URL
	 */
	public function getMathImageUrl() {
		global $wgMathPath;
		$dir = $this->getHashSubPath();
		return "$wgMathPath/$dir/{$this->getHash()}.svg";
	}

	/**
	 * Gets URL for math image
	 *
	 * @return string image URL
	 */
	public function getMathTeXFileUrl() {
		global $wgMathPath;
		$dir = $this->getHashSubPath();
		return "$wgMathPath/$dir/{$this->getHash()}.tex";
	}

	/**
	 * Gets img tag for math image
	 *
	 * @return string img HTML
	 */
	public function getMathImageHTML() {
		$url = $this->getMathImageUrl();
		$style = '';
		if ($this->getDisplaytyle()){
			$style = 'display:block;margin:auto';
		}
		return Xml::element( 'img',
			$this->getAttributes(
				'img',
				array(
					'class' => 'tex',
					'alt' => $this->getTex(),
					'style'=>$style
				),
				array(
					'src' => $url
				)
			)
		);
	}

	/**
	 * Converts an error returned by texvc to a localized exception
	 *
	 * @param string $texvcResult error result returned by texvc
	 */
	public function convertTexvcError( $texvcResult ) {
		$texvcStatus = substr( $texvcResult, 0, 1 );

		$errDetails = htmlspecialchars( substr( $texvcResult, 1 ) );
		switch( $texvcStatus ) {
			case 'E':
				$errMsg = $this->getError( 'math_lexing_error' );
				break;
			case 'S':
				$errMsg = $this->getError( 'math_syntax_error' );
				break;
			case 'F':
				$errMsg = $this->getError( 'math_unknown_function', $errDetails );
				break;
			default:
				$errMsg = $this->getError( 'math_unknown_error' );
		}

		return $errMsg;
	}

	/**
	 * Does the actual call to texvc
	 *
	 * @return int|string MW_TEXVC_SUCCESS or error string
	 */
	public function callTexvc() {
		global $wgTexvc;
		$tmpDir = wfTempDir();
		if ( !is_executable( $wgTexvc ) ) {
			return $this->getError( 'math_notexvc' );
		}

		$escapedTmpDir = wfEscapeShellArg( $tmpDir );

		$cmd = $wgTexvc . ' ' .
			$escapedTmpDir . ' ' .
			wfEscapeShellArg( $this->getTex() ). ' '.
			wfEscapeShellArg( bin2hex( $this->getInputHash()) );

		if ( wfIsWindows() ) {
			# Invoke it within cygwin sh, because texvc expects sh features in its default shell
			$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
		}
		wfDebugLog( 'Math', "TeX: $cmd\n" );
		$contents = wfShellExec( $cmd );
		wfDebugLog( 'Math', "TeX output:\n $contents\n---\n" );

		if ( strlen( $contents ) == 0 ) {
			if ( !file_exists( $tmpDir ) || !is_writable( $tmpDir ) ) {
				return $this->getError( 'math_bad_tmpdir' );
			} else {
				return $this->getError( 'math_unknown_error' );
			}
		}

		$tempFsFile = new TempFSFile( "$tmpDir/{$this->getHash()}.svg" );
		$tempFsFile->autocollect(); // destroy file when $tempFsFile leaves scope
		$tempFsFile = new TempFSFile( "$tmpDir/{$this->getHash()}.tex" );
		$tempFsFile->autocollect(); // destroy file when $tempFsFile leaves scope
		$retval = substr( $contents, 0, 1 );
		$errmsg = '';
		switch ($retval) {
			case 'C':
			case 'M':
			case 'L':
			case 'c':
			case 'm':
			case 'l':
			case 'X':
			case '+':
				break;
			default:
				$errmsg = $this->convertTexvcError( $contents );
		}
		if ( !$errmsg ) {
			$this->setHash( substr( $contents, 1, 32 ) );
		}
		//TODO: check if this was ever used
		wfRunHooks( 'MathAfterTexvc', array( &$this, &$errmsg ) );

		if ( $errmsg ) {
			return $errmsg;
		} elseif ( !preg_match( "/^[a-f0-9]{32}$/", $this->getHash() ) ) {
			return $this->getError( 'math_unknown_error' );
		} elseif ( !file_exists( "$tmpDir/{$this->getHash()}.svg" ) ) {
			return $this->getError( 'math_image_error' );
		} elseif ( filesize( "$tmpDir/{$this->getHash()}.svg" ) == 0 ) {
			return $this->getError( 'math_image_error' );
		}

		$hashpath = $this->getHashPath(); // final storage directory

		$backend = $this->getBackend();
		# Create any containers/directories as needed...
		if ( !$backend->prepare( array( 'dir' => $hashpath ) )->isOK() ) {
			return $this->getError( 'math_output_error' );
		}
		// Store the file at the final storage path...
		if ( !$backend->quickStore( array(
			'src' => "$tmpDir/{$this->getHash()}.svg", 'dst' => "$hashpath/{$this->getHash()}.svg"
		) )->isOK()
		) {
			return $this->getError( 'math_output_error' );
		}
//		Store the TeX source as well
		wfDebugLog('Math','Move texfile from '."$tmpDir/{$this->getHash()}.tex".' to '."$hashpath/{$this->getHash()}.tex");
		$backend->quickStore( array(
				'src' => "$tmpDir/{$this->getHash()}.tex", 'dst' => "$hashpath/{$this->getHash()}.tex"
		));
		return self::MW_TEXVC_SUCCESS;
	}

	/**
	 * Gets file backend
	 *
	 * @return FileBackend appropriate file backend
	 */
	public function getBackend() {
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

	/**
	 * Does the HTML rendering
	 *
	 * @return string HTML string
	 */
	public function doHTMLRender() {
		return $this->getMathImageHTML();
	}

	/**
	 * Overrides base class.  Writes to database, and if configured, squid.
	 */
	public function writeCache() {
		global $wgUseSquid;
		// If cache hit, don't write anything.
		if ( $this->isRecall() ) {
			return;
		}
		$this->writeToDatabase();
		// If we're replacing an older version of the image, make sure it's current.
		if ( $wgUseSquid ) {
			$urls = array( $this->getMathImageUrl() );
			$u = new SquidUpdate( $urls );
			$u->doUpdate();
		}
	}

	/**
	 * Reads the rendering information from the database.  If configured, checks whether files exist
	 *
	 * @return boolean true if retrieved, false otherwise
	 */
	public function readCache() {
		global $wgMathCheckFiles;
		if ( $this->readFromDatabase() ) {
			if ( !$wgMathCheckFiles ) {
				// Short-circuit the file existence & migration checks
				return true;
			}
			$filename = $this->getHashPath() . "/{$this->getHash()}.svg"; // final storage path
			$backend = $this->getBackend();
			if ( $backend->fileExists( array( 'src' => $filename ) ) ) {
				if ( $backend->getFileSize( array( 'src' => $filename ) ) == 0 ) {
					// Some horrible error corrupted stuff :(
					$backend->quickDelete( array( 'src' => $filename ) );
				} else {
					return true; // cache hit
				}
			}
		} else {
			return false;
		}
	}

	/**
	 *
	 * @return string texvc secured tex code
	 */
	public function getSecureTex(){
		$backend = $this->getBackend();
		$src= $this->getHashPath() .'/'.$this->getHash().'.tex';
		$fullTeXDocumentContent = $backend->getFileContents(array('src'=>$src));
		$parts = explode('$$', $fullTeXDocumentContent);
		$newtex = $parts[1];
		return $newtex;
	}
}