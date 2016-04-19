<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz,
 * and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains the driver function for the texvc program
 * @file
 */
use MediaWiki\Logger\LoggerFactory;

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
		$out = [];
		$dbr = wfGetDB( DB_SLAVE );
		$outmd5_sql = $dbr->encodeBlob( pack( 'H32', $this->hash ) );
		if ( $outmd5_sql instanceof Blob ) {
			$outmd5_sql = $outmd5_sql->fetch();
		}
		$out['math_outputhash'] = $outmd5_sql;
		$out['math_html_conservativeness'] = $this->conservativeness;
		$out['math_html'] = $this->html;
		$out['math_mathml'] = utf8_encode( $this->getMathml() );
		$out['math_inputhash'] = $this->getInputHash();
		LoggerFactory::getInstance( 'Math' )->debug( 'Store Hashpath of image' .
			bin2hex( $outmd5_sql ) );
		return $out;
	}

	protected function dbInArray() {
		return [ 'math_inputhash', 'math_outputhash',
				'math_html_conservativeness', 'math_html', 'math_mathml' ];
	}

	/**
	 * @param database_row $rpage
	 * @return bool
	 */
	protected function initializeFromDatabaseRow( $rpage ) {
		parent::initializeFromDatabaseRow( $rpage );
		// get deprecated fields
		if ( $rpage->math_outputhash ) {
			$dbr = wfGetDB( DB_SLAVE );
			$xhash = unpack( 'H32md5',
				$dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
			$this->hash = $xhash['md5'];
			LoggerFactory::getInstance( 'Math' )->debug( 'Hashpath of PNG-File:' .
				bin2hex( $this->hash ) );
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
	public function render() {
		if ( !$this->readCache() ) { // cache miss
			$result = $this->callTexvc();
			if ( $result === self::MW_TEXVC_SUCCESS ) {
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
		LoggerFactory::getInstance( 'Math' )->debug(
			"TeX: getHashPath, hash is: {$this->getHash()}, path is: $path" );
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
		$attributes = [
			// the former class name was 'tex'
			// for backwards compatibility we keep that classname
			'class' => 'mwe-math-fallback-image-inline tex',
			'alt' => $this->getTex()
		];
		if ( $this->getMathStyle() === 'display' ){
			// if DisplayStyle is true, the equation will be centered in a new line
			$attributes[ 'class' ] = 'mwe-math-fallback-image-display tex';
		}
		return Xml::element( 'img',
			$this->getAttributes(
				'img',
				$attributes,
				[
					'src' => $url
				]
			)
		);

	}

	/**
	 * Converts an error returned by texvc to a localized exception
	 *
	 * @param string $texvcResult error result returned by texvc
	 * @return string
	 */
	public function convertTexvcError( $texvcResult ) {
		$errorConverter = new MathInputCheckTexvc();
		return $errorConverter->convertTexvcError( $texvcResult, $this );
	}

	/**
	 * Does the actual call to texvc
	 *
	 * @return int|string MW_TEXVC_SUCCESS or error string
	 */
	public function callTexvc() {
		global $wgTexvc, $wgTexvcBackgroundColor, $wgHooks;
		if ( $wgTexvc === false ){
			$texvc = __DIR__ . '/math/texvc';
		} else {
			$texvc = $wgTexvc;
		}
		$tmpDir = wfTempDir();
		if ( !is_executable( $texvc ) ) {
			LoggerFactory::getInstance( 'Math' )->error(
				"$texvc does not exist or is not executable." );
			return $this->getError( 'math_notexvc' );
		}

		$escapedTmpDir = wfEscapeShellArg( $tmpDir );

		$cmd = $texvc . ' ' .
			$escapedTmpDir . ' ' .
			$escapedTmpDir . ' ' .
			wfEscapeShellArg( $this->getUserInputTex() ) . ' ' .
			wfEscapeShellArg( 'UTF-8' ) . ' ' .
			wfEscapeShellArg( $wgTexvcBackgroundColor );

		if ( wfIsWindows() ) {
			# Invoke it within cygwin sh, because texvc expects sh features in its default shell
			$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
		}
		LoggerFactory::getInstance( 'Math' )->debug( "TeX: $cmd" );
		LoggerFactory::getInstance( 'Math' )->debug( "Executing '$cmd'." );
		$retval = null;
		$contents = wfShellExec( $cmd, $retval );
		LoggerFactory::getInstance( 'Math' )->debug( "TeX output:\n $contents\n---" );

		if ( strlen( $contents ) == 0 ) {
			if ( !file_exists( $tmpDir ) || !is_writable( $tmpDir ) ) {
				LoggerFactory::getInstance( 'Math' )->error(
					"TeX output directory $tmpDir is missing or not writable" );
				return $this->getError( 'math_bad_tmpdir' );
			} else {
				LoggerFactory::getInstance( 'Math' )->error(
					"TeX command '$cmd' returned no output and status code $retval." );
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
				$this->setConservativeness( self::MODERATE );
			} else {
				$this->setConservativeness( self::LIBERAL );
			}
			$outdata = substr( $contents, 33 );

			$i = strpos( $outdata, "\000" );

			$this->setHtml( substr( $outdata, 0, $i ) );
			$this->setMathml( substr( $outdata, $i + 1 ) );
		} elseif ( ( $retval == 'c' ) || ( $retval == 'm' ) || ( $retval == 'l' ) ) {
			$this->setHtml( substr( $contents, 33 ) );
			if ( $retval == 'c' ) {
				$this->setConservativeness( self::CONSERVATIVE );
			} elseif ( $retval == 'm' ) {
				$this->setConservativeness( self::MODERATE );
			} else {
				$this->setConservativeness( self::LIBERAL );
			}
			$this->setMathml( null );
		} elseif ( $retval == 'X' ) {
			$this->setHtml( null );
			$this->setMathml( substr( $contents, 33 ) );
			$this->setConservativeness( self::LIBERAL );
		} elseif ( $retval == '+' ) {
			$this->setHtml( null );
			$this->setMathml( null );
			$this->setConservativeness( self::LIBERAL );
		} else {
			$errmsg = $this->convertTexvcError( $contents );
		}

		if ( !$errmsg ) {
			$newHash = substr( $contents, 1, 32 );
			if ( $this->hash !== $newHash ) {
				$this->isInDatabase( false ); // DB needs update in writeCache() (bug 60997)
			}
			$this->setHash( $newHash );
		}

		Hooks::run( 'MathAfterTexvc', [ &$this, &$errmsg ] );

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
		if ( !$backend->prepare( [ 'dir' => $hashpath ] )->isOK() ) {
			return $this->getError( 'math_output_error' );
		}
		// Store the file at the final storage path...
		// Bug 56769: buffer the writes and do them at the end.
		if ( !isset( $wgHooks['ParserAfterParse']['FlushMathBackend'] ) ) {
			$backend->mathBufferedWrites = [];
			$wgHooks['ParserAfterParse']['FlushMathBackend'] = function () use ( $backend ) {
				global $wgHooks;
				unset( $wgHooks['ParserAfterParse']['FlushMathBackend'] );
				$backend->doQuickOperations( $backend->mathBufferedWrites );
				unset( $backend->mathBufferedWrites );
			};
		}
		$backend->mathBufferedWrites[] = [
			'op'  => 'store',
			'src' => "$tmpDir/{$this->getHash()}.png",
			'dst' => "$hashpath/{$this->getHash()}.png",
			'ref' => $tempFsFile // keep file alive
		];

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
				$backend = new FSFileBackend( [
					'name'           => 'math-backend',
					'wikiId' 	 => wfWikiId(),
					'lockManager'    => new NullLockManager( [] ),
					'containerPaths' => [ 'math-render' => $wgMathDirectory ],
					'fileMode'       => 0777
				] );
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
		return $this->getMathImageHTML();
	}

	/**
	 * Overrides base class.  Writes to database, and if configured, squid.
	 */
	public function writeCache() {
		global $wgUseSquid;

		$updated = parent::writeCache();
		// If we're replacing an older version of the image, make sure it's current.
		if ( $updated && $wgUseSquid ) {
			$urls = [ $this->getMathImageUrl() ];
			$u = new SquidUpdate( $urls );
			$u->doUpdate();
		}

		return $updated;
	}

	/**
	 * Reads the rendering information from the database.  If configured, checks whether files exist
	 *
	 * @return boolean true if retrieved, false otherwise
	 */
	public function readCache() {
		global $wgMathCheckFiles;

		if ( $this->isInDatabase() ) {
			if ( !$wgMathCheckFiles ) {
				// Short-circuit the file existence & migration checks
				return true;
			}
			$filename = $this->getHashPath() . "/{$this->getHash()}.png"; // final storage path
			$backend = $this->getBackend();
			if ( $backend->fileExists( [ 'src' => $filename ] ) ) {
				if ( $backend->getFileSize( [ 'src' => $filename ] ) == 0 ) {
					// Some horrible error corrupted stuff :(
					$backend->quickDelete( [ 'src' => $filename ] );
				} else {
					return true; // cache hit
				}
			}
		}
		return false;
	}

	public function getPng() {
		global $wgHooks;
		// Workaround for bugfix for Bug 56769
		if ( isset( $wgHooks['ParserAfterParse']['FlushMathBackend'] ) ) {
			// XXX: save any pending files so the read below works
			call_user_func( $wgHooks['ParserAfterParse']['FlushMathBackend'] );
		}
		$backend = $this->getBackend();
		return $backend->getFileContents(
			[ 'src' => $this->getHashPath() . "/" . $this->getHash() . '.png' ]
		);
	}

	public function isInDatabase() {
		$return = parent::isInDatabase();
		if ( $this->hash && $return ) {
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

	protected function getMathTableName() {
		return 'math';
	}

	public function setOutputHash( $hash ) {
		$this->hash = $hash;
	}

	/**
	 * Skip tex check for texvc rendering mode.
	 * Checking the tex code in texvc mode just adds a dependency to the
	 * texvccheck binary which does not improve security since the same
	 * checks are performed by texvc anyhow. Especially given the fact that
	 * texvccheck was derived from texvc.
	 * @return bool
	 */
	public function checkTeX() {
		return true;
	}

}
