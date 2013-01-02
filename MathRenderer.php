<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains everything related to <math> </math> parsing
 * @file
 * @ingroup Parser
 */

if ( !function_exists('wfEscapeSingleQuotes') ) {
	/**
	 * Escapes a string with single quotes for a UNIX shell.
	 * It's equivalent to escapeshellarg() in UNIX, but also
	 * working in Windows, where we need it for cygwin shell.
	 */
	function wfEscapeSingleQuotes( $str ) {
		return "'" . str_replace( "'", "'\\''", $str ) . "'";
	}
}

/**
 * Takes LaTeX fragments, sends them to a helper program (texvc) for rendering
 * to rasterized PNG and HTML and MathML approximations. An appropriate
 * rendering form is picked and returned.
 *
 * @author Tomasz Wegrzanowski, with additions by Brion Vibber (2003, 2004)
 * @ingroup Parser
 */
class MathRenderer {
	var $mode = MW_MATH_PNG;
	var $tex = '';
	var $inputhash = '';
	var $hash = '';
	var $html = '';
	var $mathml = '';
	var $conservativeness = 0;

	public function __construct( $tex, $params = array() ) {
		$this->tex = $tex;
		$this->params = $params;
	}

	/**
	 * @return FileBackend
	 */
	protected function getBackend() {
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

	function setOutputMode( $mode ) {
		$validModes = array( MW_MATH_PNG, MW_MATH_SOURCE, MW_MATH_MATHJAX );
		if ( in_array( $mode, $validModes ) ) {
			$this->mode = $mode;
		} else {
			// Several mixed modes have been phased out.
			$this->mode = MW_MATH_PNG;
		}
	}

	function render() {
		global $wgTexvc, $wgTexvcBackgroundColor, $wgUseSquid;

		if( $this->mode == MW_MATH_SOURCE || $this->mode == MW_MATH_MATHJAX ) {
			# No need to render or parse anything more!
			# New lines are replaced with spaces, which avoids confusing our parser (bugs 23190, 22818)
			return Xml::element( 'span',
				$this->_attribs(
					'span',
					array(
						'class' => 'tex',
						'dir' => 'ltr'
					)
				),
				'$ ' . str_replace( "\n", " ", $this->tex ) . ' $'
			);
		}
		if( $this->tex == '' ) {
			return; # bug 8372
		}

		$tmpDir = wfTempDir();
		if( !$this->_recall() ) { // cache miss
			if( !is_executable( $wgTexvc ) ) {
				return $this->_error( 'math_notexvc' );
			}
			$cmd = $wgTexvc . ' ' .
				wfEscapeSingleQuotes( $tmpDir ) . ' '.
				wfEscapeSingleQuotes( $tmpDir ) . ' '.
				wfEscapeSingleQuotes( $this->tex ) . ' '.
				wfEscapeSingleQuotes( 'UTF-8' ) . ' '.
				wfEscapeSingleQuotes( $wgTexvcBackgroundColor );

			if ( wfIsWindows() ) {
				# Invoke it within cygwin sh, because texvc expects sh features in its default shell
				$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
			}

			wfDebug( "TeX: $cmd\n" );
			$contents = wfShellExec( $cmd );
			wfDebug( "TeX output:\n $contents\n---\n" );

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
			} elseif( !file_exists( "$tmpDir/{$this->hash}.png" ) ) {
				return $this->_error( 'math_image_error' );
			} elseif( filesize( "$tmpDir/{$this->hash}.png" ) == 0 ) {
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

			# Now save it back to the DB:
			if ( !wfReadOnly() ) {
				$outmd5_sql = pack( 'H32', $this->hash );

				$md5_sql = pack( 'H32', $this->md5 ); # Binary packed, not hex

				$dbw = wfGetDB( DB_MASTER );
				$dbw->replace(
					'math',
					array( 'math_inputhash' ),
					array(
						'math_inputhash' => $dbw->encodeBlob( $md5_sql ),
						'math_outputhash' => $dbw->encodeBlob( $outmd5_sql ),
						'math_html_conservativeness' => $this->conservativeness,
						'math_html' => $this->html,
						'math_mathml' => $this->mathml,
					),
					__METHOD__
				);
			}

			// If we're replacing an older version of the image, make sure it's current.
			if ( $wgUseSquid ) {
				$urls = array( $this->_mathImageUrl() );
				$u = new SquidUpdate( $urls );
				$u->doUpdate();
			}
		}

		return $this->_doRender();
	}

	function _error( $msg, $append = '' ) {
		$mf = wfMessage( 'math_failure' )->inContentLanguage()->escaped();
		$errmsg = wfMessage( $msg )->inContentLanguage()->escaped();
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return "<strong class='error'>$mf ($errmsg$append): $source</strong>\n";
	}

	function _recall() {
		global $wgMathCheckFiles;

		$this->md5 = md5( $this->tex );
		$dbr = wfGetDB( DB_SLAVE );
		$rpage = $dbr->selectRow(
			'math',
			array(
				'math_outputhash', 'math_html_conservativeness', 'math_html',
				'math_mathml'
			),
			array(
				'math_inputhash' => $dbr->encodeBlob( pack( "H32", $this->md5 ) ) # Binary packed, not hex
			),
			__METHOD__
		);

		if( $rpage !== false ) {
			# Trailing 0x20s can get dropped by the database, add it back on if necessary:
			$xhash = unpack( 'H32md5', $dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
			$this->hash = $xhash['md5'];

			$this->conservativeness = $rpage->math_html_conservativeness;
			$this->html = $rpage->math_html;
			$this->mathml = $rpage->math_mathml;

			if( !$wgMathCheckFiles ) {
				// Short-circuit the file existence & migration checks
				return true;
			}

			$filename = $this->_getHashPath() . "/{$this->hash}.png"; // final storage path

			$backend = $this->getBackend();
			if( $backend->fileExists( array( 'src' => $filename ) ) ) {
				if( $backend->getFileSize( array( 'src' => $filename ) ) == 0 ) {
					// Some horrible error corrupted stuff :(
					$backend->quickDelete( array( 'src' => $filename ) );
				} else {
					return true; // cache hit
				}
			}
		}

		# Missing from the database and/or the render cache
		return false;
	}

	/**
	 * Select among PNG, HTML, or MathML output depending on
	 */
	function _doRender() {
		if( $this->mode == MW_MATH_MATHML && $this->mathml != '' ) {
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

	function _attribs( $tag, $defaults = array(), $overrides = array() ) {
		$attribs = Sanitizer::validateTagAttributes( $this->params, $tag );
		$attribs = Sanitizer::mergeAttributes( $defaults, $attribs );
		$attribs = Sanitizer::mergeAttributes( $attribs, $overrides );
		return $attribs;
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

	function _mathImageUrl() {
		global $wgMathPath;
		$dir = $this->_getHashSubPath();
		return "$wgMathPath/$dir/{$this->hash}.png";
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
		return substr( $this->hash, 0, 1)
					. '/' . substr( $this->hash, 1, 1 )
					. '/' . substr( $this->hash, 2, 1 );
	}

	public static function renderMath( $tex, $params = array(), ParserOptions $parserOptions = null ) {
		if( trim( $tex ) == "" ) {
			return "";
		}

		$math = new MathRenderer( $tex, $params );
		if ( $parserOptions ) {
			$math->setOutputMode( $parserOptions->getMath() );
		}
		return $math->render();
	}
}
