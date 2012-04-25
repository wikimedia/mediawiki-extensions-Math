<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains everything related to <math> </math> parsing
 * 2012/04/25 Changed LaTeXML for the MathML rendering which is passed to MathJAX
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

	function __construct( $tex, $params = array() ) {
		$this->tex = $tex;
		$this->params = $params;
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
		global $wgTmpDirectory;
		global $wgTexvc, $wgMathCheckFiles, $wgTexvcBackgroundColor;
		if( $this->mode == MW_MATH_SOURCE ) {
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
				'$ ' . str_replace( "\n", " ", htmlspecialchars( $this->tex ) ) . ' $'
			);
		}

		
		if( $this->tex == '' ) {
			return; # bug 8372
		}

		if( !$this->_recall() ) {
		$latexmlmath=new MathLaTeXML();
		$latexmlmath->render($this);
			if ($this->mode == MW_MATH_MATHJAX){
				if ( !wfReadOnly() ) {
				if (!$this->hash)
					$this->hash = md5( $this->tex );
				$outmd5_sql = pack( 'H32', $this->hash );

				$md5_sql = pack( 'H32', $this->md5 ); # Binary packed, not hex

				$dbw = wfGetDB( DB_MASTER );
				$dbw->replace(
					'math',
					array( 'math_inputhash' ),
					array(
						'math_inputhash' => $dbw->encodeBlob( $md5_sql ),
						'math_outputhash' => $dbw->encodeBlob( $outmd5_sql ),
						'math_mathml' => $this->mathml,
					),
					__METHOD__
				);
			}
			}else{
			if( $wgMathCheckFiles ) {
				# Ensure that the temp and output directories are available before continuing...
				if( !file_exists( $wgTmpDirectory ) ) {
					if( !wfMkdirParents( $wgTmpDirectory, null, __METHOD__ ) ) {
						return $this->_error( 'math_bad_tmpdir' );
					}
				} elseif( !is_dir( $wgTmpDirectory ) || !is_writable( $wgTmpDirectory ) ) {
					return $this->_error( 'math_bad_tmpdir' );
				}
			}
			$texvc = new MathTexvc();
			$result=$texvc->render($this);


			if ( !preg_match( "/^[a-f0-9]{32}$/", $this->hash ) ) {
				return $this->_error( 'math_unknown_error' );
			}

			if( !file_exists( "$wgTmpDirectory/{$this->hash}.png" ) ) {
				return $this->_error( 'math_image_error' );
			}

			if( filesize( "$wgTmpDirectory/{$this->hash}.png" ) == 0 ) {
				return $this->_error( 'math_image_error' );
			}

			$hashpath = $this->_getHashPath();
			if( !file_exists( $hashpath ) ) {
				wfSuppressWarnings();
				$ret = wfMkdirParents( $hashpath, 0755, __METHOD__ );
				wfRestoreWarnings();
				if( !$ret ) {
					return $this->_error( 'math_bad_output' );
				}
			} elseif( !is_dir( $hashpath ) || !is_writable( $hashpath ) ) {
				return $this->_error( 'math_bad_output' );
			}

			if( !rename( "$wgTmpDirectory/{$this->hash}.png", "$hashpath/{$this->hash}.png" ) ) {
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
			global $wgUseSquid;
			if ( $wgUseSquid ) {
				$urls = array( $this->_mathImageUrl() );
				$u = new SquidUpdate( $urls );
				$u->doUpdate();
			}
		}}

		return $this->_doRender();
	}

	function _error( $msg, $append = '' ) {
		$mf = htmlspecialchars( wfMsg( 'math_failure' ) );
		$errmsg = htmlspecialchars( wfMsg( $msg ) );
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return "<strong class='error'>$mf ($errmsg$append): $source</strong>\n";
	}

	function _recall() {
		global $wgMathDirectory, $wgMathCheckFiles;

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
			# Tailing 0x20s can get dropped by the database, add it back on if necessary:
			$xhash = unpack( 'H32md5', $dbr->decodeBlob( $rpage->math_outputhash ) . "                " );
			$this->hash = $xhash['md5'];

			$this->conservativeness = $rpage->math_html_conservativeness;
			$this->html = $rpage->math_html;
			$this->mathml = $rpage->math_mathml;

			$filename = $this->_getHashPath() . "/{$this->hash}.png";

			if( !$wgMathCheckFiles ) {
				// Short-circuit the file existence & migration checks
				return true;
			}

			if( file_exists( $filename ) ) {
				if( filesize( $filename ) == 0 ) {
					// Some horrible error corrupted stuff :(
					wfSuppressWarnings();
					unlink( $filename );
					wfRestoreWarnings();
				} else {
					return true;
				}
			}

			if( file_exists( $wgMathDirectory . "/{$this->hash}.png" ) ) {
				$hashpath = $this->_getHashPath();

				if( !file_exists( $hashpath ) ) {
					wfSuppressWarnings();
					$ret = wfMkdirParents( $hashpath, 0755, __METHOD__ );
					wfRestoreWarnings();
					if( !$ret ) {
						return false;
					}
				} elseif( !is_dir( $hashpath ) || !is_writable( $hashpath ) ) {
					return false;
				}
				if ( function_exists( 'link' ) ) {
					return link( $wgMathDirectory . "/{$this->hash}.png",
							$hashpath . "/{$this->hash}.png" );
				} else {
					return rename( $wgMathDirectory . "/{$this->hash}.png",
							$hashpath . "/{$this->hash}.png" );
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
		if( $this->mode == MW_MATH_MATHML && $this->mathml != '' or $this->mode == MW_MATH_MATHJAX ) {
			return $this->_embedMathML();
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

	function _embedMathML() {
		$mml=str_replace("\n"," ",$this->mathml);
			return Xml::tags( 'span',
				$this->_attribs( 'span',
					array(
						'class' => 'tex',
						'dir' => 'ltr',
						'id' => $this->hash
					)
				),
				$mml
		);
	}

	function _mathImageUrl() {
		global $wgMathPath;
		$dir = $this->_getHashSubPath();
		return "$wgMathPath/$dir/{$this->hash}.png";
	}

	function _getHashPath() {
		global $wgMathDirectory;
		$path = $wgMathDirectory . '/' . $this->_getHashSubPath();
		wfDebug( "TeX: getHashPath, hash is: $this->hash, path is: $path\n" );
		return $path;
	}

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
