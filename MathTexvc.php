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
 * @deprecated will be deleted in one of the next versions without further notice
 */
class MathTexvc extends MathRenderer {
	private $hash = '';
	private $html = '';
	private $conservativeness = 0;
	const CONSERVATIVE = 2;
	const MODERATE = 1;
	const LIBERAL = 0;
	const MW_TEXVC_SUCCESS = -1;

	/**
	 * Gets an array that matches the variables of the class to the database columns
	 * @return array
	 */
	public function dbOutArray() {
		$out = parent::dbOutArray();
		$dbr = wfGetDB( DB_SLAVE );
		if ( $this->hash ) {
			$outmd5_sql = $dbr->encodeBlob( pack( 'H32', $this->hash ) );
		} else {
			$outmd5_sql = 0; // field cannot be null
			// TODO: Change Database layout to allow for null values
		}
		$out['math_outputhash'] = $outmd5_sql;
		$out['math_html_conservativeness'] = $this->conservativeness;
		$out['math_html'] = $this->html;
		wfDebugLog( 'Math', 'Store Hashpath of image'. bin2hex($outmd5_sql));
		return $out;
	}

	public function dbInArray() {
		$deprecatedfields = array('math_outputhash',
				'math_html_conservativeness',
				'math_html');
		$newFields = parent::dbInArray();

		return array_merge ( $newFields, $deprecatedfields );
	}
	/**
	 * @param database_row $rpage
	 */
	 function initializeFromDatabaseRow( $rpage ) {
		$result = parent::initializeFromDatabaseRow($rpage);
		//get deprecated fields
		if ( $rpage->math_outputhash ){
			$dbr = wfGetDB( DB_SLAVE );
			$xhash = unpack( 'H32md5',
				$dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
			$this->hash = $xhash['md5'];
			wfDebugLog( 'Math', 'Hashpath of PNG-File:'.bin2hex($this->hash));
			$this->conservativeness = $rpage->math_html_conservativeness;
			$this->html = $rpage->math_html;
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Renders TeX using texvc
	 *
	 * @return string rendered TeK
	 */
	 function render() {
		if ( !$this->readCache() ) { // cache miss
			$result = $this->callTexvc();
			if ( $result == self::MW_TEXVC_SUCCESS ) {
				return true;
			} else {
				$this->lastError = $result;
				return false;
			}
		}
		return true;
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
		return "$wgMathPath/$dir/{$this->getHash()}.png";
	}

	/**
	 * Gets img tag for math image
	 *
	 * @return string img HTML
	 */
	public function getMathImageHTML() {
		$url = $this->getMathImageUrl();

		return Xml::element( 'img',
			$this->getAttributes(
				'img',
				array(
					'class' => 'tex',
					'alt' => $this->getTex(),
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
		global $wgTexvc, $wgTexvcBackgroundColor;
		$tmpDir = wfTempDir();
		if ( !is_executable( $wgTexvc ) ) {
			return $this->getError( 'math_notexvc' );
		}

		$escapedTmpDir = wfEscapeShellArg( $tmpDir );

		$cmd = $wgTexvc . ' ' .
			$escapedTmpDir . ' ' .
			$escapedTmpDir . ' ' .
			wfEscapeShellArg( $this->getUserInputTex() ) . ' ' .
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
				return $this->getError( 'math_bad_tmpdir' );
			} else {
				return $this->getError( 'math_unknown_error' );
			}
		}

		$tempFsFile = new TempFSFile( "$tmpDir/{$this->getHash()}.png" );
		$tempFsFile->autocollect(); // destroy file when $tempFsFile leaves scope

		$retval = substr( $contents, 0, 1 );
		$errmsg = '';
		if ( ( $retval == 'C' ) || ( $retval == 'M' ) || ( $retval == 'L' ) ) {
			if ( $retval == 'C' ) {
				$this->setConservativeness( self::CONSERVATIVE );
			} elseif ( $retval == 'M' ) {
				$this->setConservativeness(  self::MODERATE );
			} else {
				$this->setConservativeness(  self::LIBERAL );
			}
			$outdata = substr( $contents, 33 );

			$i = strpos( $outdata, "\000" );

			$this->setHtml( substr( $outdata, 0, $i ) );
		} elseif ( ( $retval == 'c' ) || ( $retval == 'm' ) || ( $retval == 'l' ) ) {
			$this->setHtml( substr( $contents, 33 ) );
			if ( $retval == 'c' ) {
				$this->setConservativeness( self::CONSERVATIVE ) ;
			} elseif ( $retval == 'm' ) {
				$this->setConservativeness( self::MODERATE );
			} else {
				$this->setConservativeness( self::LIBERAL );
			}
		} elseif ( $retval == 'X' ) {
			$this->setHtml( null );
			$this->setConservativeness( self::LIBERAL );
		} elseif ( $retval == '+' ) {
			$this->setHtml( null );
			$this->setConservativeness( self::LIBERAL );
		} else {
			$errmsg = $this->convertTexvcError( $contents );
		}

		if ( !$errmsg ) {
			$this->setHash( substr( $contents, 1, 32 ) );
		}

		wfRunHooks( 'MathAfterTexvc', array( &$this, &$errmsg ) );

		if ( $errmsg ) {
			return $errmsg;
		} elseif ( !preg_match( "/^[a-f0-9]{32}$/", $this->getHash() ) ) {
			return $this->getError( 'math_unknown_error' );
		} elseif ( !file_exists( "$tmpDir/{$this->getHash()}.png" ) ) {
			return $this->getError( 'math_image_error' );
		} elseif ( filesize( "$tmpDir/{$this->getHash()}.png" ) == 0 ) {
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
			'src' => "$tmpDir/{$this->getHash()}.png", 'dst' => "$hashpath/{$this->getHash()}.png"
		) )->isOK()
		) {
			return $this->getError( 'math_output_error' );
		}
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
	public function getHtmlOutput() {
		if ( ( $this->getMode() == MW_MATH_PNG ) || ( $this->getHtml() == '' ) ||
			( ( $this->getMode() == MW_MATH_SIMPLE ) && ( $this->getConservativeness() != self::CONSERVATIVE ) ) ||
			( ( $this->getMode() == MW_MATH_MODERN || $this->getMode() == MW_MATH_MATHML ) && ( $this->getConservativeness() == self::LIBERAL ) )
		)
		{
			return $this->getMathImageHTML();
		} else {
			return Xml::tags( 'span',
				$this->getAttributes( 'span',
					array( 'class' => 'texhtml',
						'dir' => 'ltr'
					) ),
				$this->getHtml()
			);
		}
	}

	/**
	 * Overrides base class.  Writes to database, and if configured, squid.
	 */
	public function writeCache() {
		global $wgUseSquid;
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
			$filename = $this->getHashPath() . "/{$this->getHash()}.png"; // final storage path
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
	public function getPng(){
		$backend = $this->getBackend();
		echo $this->getHashPath(). "/". $this->getHash() . '.png';
		return $backend->getFileContents( array('src'=>$this->getHashPath(). "/". $this->getHash() . '.png' ) );
	}

	public function readFromDatabase() {
		$return = parent::readFromDatabase();
		if ( $this->hash  && $return ){
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Get the hash calculated by texvc
	 *
	 * @return string hash
	 */
	public function getHash() {
		return $this->hash;
	}

	/**
	 * @param string $hash
	 */
	public function setHash( $hash ) {
		$this->changed = true;
		$this->hash = $hash;
	}

	/**
	 * Returns the html-representation of the mathematical formula.
	 * @return string
	 */
	public function getHtml() {
		return $this->html;
	}

	/**
	 * @param string $html
	 */
	public function setHtml( $html ) {
		$this->changed = true;
		$this->html = $html;
	}

		/**
	 * Gets the so called 'conservativeness' calculated by texvc
	 *
	 * @return int
	 */
	public function getConservativeness() {
		return $this->conservativeness;
	}

	/**
	 * @param int $conservativeness
	 */
	public function setConservativeness( $conservativeness ) {
		$this->changed = true;
		$this->conservativeness = $conservativeness;
	}


}