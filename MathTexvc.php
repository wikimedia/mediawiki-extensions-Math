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
class MathTexvc extends MathRenderer {

	function render() {
		if ( trim( $this->tex ) == '' ) {
			return; # bug 8372
		}
		if ( !$this->_recall() ) { // cache miss
			$result = $this->callTexvc();
			if ( $result != MW_TEXVC_SUCCESS )
				return $result;
		}
		return $this->_doRender();
	}

	/**
	 * @return string Storage directory
	 */
	function _getHashPath() {
		$path = $this->getBackend()->getRootStoragePath() .
		'/math-render/' . $this->_getHashSubPath();
		wfDebug( "TeX: getHashPath, hash is: $this->hash, path is: $path\n" );
		return $path;
	}

	/**
	 * @return string Relative directory
	 */
	function _getHashSubPath() {
		return substr( $this->hash, 0, 1 )
		. '/' . substr( $this->hash, 1, 1 )
		. '/' . substr( $this->hash, 2, 1 );
	}

	function _mathImageUrl() {
		global $wgMathPath;
		$dir = $this->_getHashSubPath();
		return "$wgMathPath/$dir/{$this->hash}.png";
	}
	function _linkToMathImage() {
		$url = $this->_mathImageUrl();

		return Xml::element( 'img',
				$this->_attribs(
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
	function callTexvc() {
		$tmpDir = wfTempDir();
		global $wgTmpDirectory;
		global $wgTexvc, $wgTexvcBackgroundColor, $wgUseSquid;
		global $wgTexvc, $wgMathCheckFiles, $wgTexvcBackgroundColor;
		if ( !is_executable( $wgTexvc ) ) {
			return $this->_error( 'math_notexvc' );
		}
		$cmd = $wgTexvc . ' ' .
				wfEscapeShellArg( $tmpDir ) . ' ' .
				wfEscapeShellArg( $tmpDir ) . ' ' .
				wfEscapeShellArg( $this->tex ) . ' ' .
				wfEscapeShellArg( 'UTF-8' ) . ' ' .
				wfEscapeShellArg( $wgTexvcBackgroundColor );

		if ( wfIsWindows() ) {
			# Invoke it within cygwin sh, because texvc expects sh features in its default shell
			$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
		}

		wfDebugLog( 'Math', "TeX: $cmd\n" );
		$contents = wfShellExec( $cmd );
		wfDebugLog( 'Math', "TeX output:\n $contents\n---\n" );

		if ( strlen( $contents ) == 0 ) {
			if ( !file_exists( $tmpDir ) || !is_writable( $tmpDir ) ) {
				return $this->_error( 'math_bad_tmpdir' );
			} else {
				return $this->_error( 'math_unknown_error' );
			}
		}

		$tempFsFile = new TempFSFile( "$tmpDir/{$this->hash}.png" );
		$tempFsFile->autocollect(); // destroy file when $tempFsFile leaves scope

		$retval = substr( $contents, 0, 1 );
		$errmsg = '';
		if ( ( $retval == 'C' ) || ( $retval == 'M' ) || ( $retval == 'L' ) ) {
			if ( $retval == 'C' ) {
				$this->conservativeness = 2;
			} elseif ( $retval == 'M' ) {
				$this->conservativeness = 1;
			} else {
				$this->conservativeness = 0;
			}
			$outdata = substr( $contents, 33 );

			$i = strpos( $outdata, "\000" );

			$this->html = substr( $outdata, 0, $i );
			$this->mathml = substr( $outdata, $i + 1 );
		} elseif ( ( $retval == 'c' ) || ( $retval == 'm' ) || ( $retval == 'l' ) ) {
			$this->html = substr( $contents, 33 );
			if ( $retval == 'c' ) {
				$this->conservativeness = 2;
			} elseif ( $retval == 'm' ) {
				$this->conservativeness = 1;
			} else {
				$this->conservativeness = 0;
			}
			$this->mathml = null;
		} elseif ( $retval == 'X' ) {
			$this->html = null;
			$this->mathml = substr( $contents, 33 );
			$this->conservativeness = 0;
		} elseif ( $retval == '+' ) {
			$this->html = null;
			$this->mathml = null;
			$this->conservativeness = 0;
		} else {
			$errbit = htmlspecialchars( substr( $contents, 1 ) );
			switch( $retval ) {
				case 'E':
					$errmsg = $this->_error( 'math_lexing_error', $errbit );
					break;
				case 'S':
					$errmsg = $this->_error( 'math_syntax_error', $errbit );
					break;
				case 'F':
					$errmsg = $this->_error( 'math_unknown_function', $errbit );
					break;
				default:
					$errmsg = $this->_error( 'math_unknown_error', $errbit );
			}
		}

		if ( !$errmsg ) {
			$this->hash = substr( $contents, 1, 32 );
		}

		wfRunHooks( 'MathAfterTexvc', array( &$this, &$errmsg ) );

		if ( $errmsg ) {
			return $errmsg;
		} elseif ( !preg_match( "/^[a-f0-9]{32}$/", $this->hash ) ) {
			return $this->_error( 'math_unknown_error' );
		} elseif ( !file_exists( "$tmpDir/{$this->hash}.png" ) ) {
			return $this->_error( 'math_image_error' );
		} elseif ( filesize( "$tmpDir/{$this->hash}.png" ) == 0 ) {
			return $this->_error( 'math_image_error' );
		}

		$hashpath = $this->_getHashPath(); // final storage directory

		$backend = $this->getBackend();
		# Create any containers/directories as needed...
		if ( !$backend->prepare( array( 'dir' => $hashpath ) )->isOK() ) {
			return $this->_error( 'math_output_error' );
		}
		// Store the file at the final storage path...
		if ( !$backend->quickStore( array(
				'src' => "$tmpDir/{$this->hash}.png", 'dst' => "$hashpath/{$this->hash}.png"
		) )->isOK()
		) {
			return $this->_error( 'math_output_error' );
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
	function _doRender() {
		if ( $this->mode == MW_MATH_MATHML && $this->mathml != '' ) {
			return Xml::tags( 'math',
					$this->_attribs( 'math',
							array( 'xmlns' => 'http://www.w3.org/1998/Math/MathML' ) ),
					$this->mathml );
		}
		if ( ( $this->mode == MW_MATH_PNG ) || ( $this->html == '' ) ||
				( ( $this->mode == MW_MATH_SIMPLE ) && ( $this->conservativeness != 2 ) ) ||
				( ( $this->mode == MW_MATH_MODERN || $this->mode == MW_MATH_MATHML ) && ( $this->conservativeness == 0 ) )
		)
		{
			return $this->_linkToMathImage();
		} else {
			return Xml::tags( 'span',
					$this->_attribs( 'span',
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
		$this->_writeDBentry();
		// If we're replacing an older version of the image, make sure it's current.
		if ( $wgUseSquid ) {
			$urls = array( $this->_mathImageUrl() );
			$u = new SquidUpdate( $urls );
			$u->doUpdate();
		}
	}
	function _recall() {
		global $wgMathCheckFiles;
		if ( $this->_readFromDB() ) {
			if ( !$wgMathCheckFiles ) {
				// Short-circuit the file existence & migration checks
				return true;
			}
			$filename = $this->_getHashPath() . "/{$this->hash}.png"; // final storage path
			$backend = $this->getBackend();
			if ( $backend->fileExists( array( 'src' => $filename ) ) ) {
				if ( $backend->getFileSize( array( 'src' => $filename ) ) == 0 ) {
					// Some horrible error corrupted stuff :(
					$backend->quickDelete( array( 'src' => $filename ) );
				} else {
					return true; // cache hit
				}
			}
		}
	}

}