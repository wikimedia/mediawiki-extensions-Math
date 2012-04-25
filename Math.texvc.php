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

class MathTexvc{

	function render($super) {
	//22.04.12 MathML generation was removed. LaTeXML does that much better
		global $wgTmpDirectory;
		global $wgTexvc, $wgMathCheckFiles, $wgTexvcBackgroundColor;
			if( !is_executable( $wgTexvc ) ) {
				return $super->_error( 'math_notexvc' );
			}
			$cmd = $wgTexvc . ' ' .
					wfEscapeSingleQuotes( $wgTmpDirectory ) . ' '.
					wfEscapeSingleQuotes( $wgTmpDirectory ) . ' '.
					wfEscapeSingleQuotes( $super->tex ) . ' '.
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
				return $super->_error( 'math_unknown_error' );
			}

			$retval = substr( $contents, 0, 1 );
			$errmsg = '';
			if ( ( $retval == 'C' ) || ( $retval == 'M' ) || ( $retval == 'L' ) ) {
				if ( $retval == 'C' ) {
					$super->conservativeness = 2;
				} elseif ( $retval == 'M' ) {
					$super->conservativeness = 1;
				} else {
					$super->conservativeness = 0;
				}
				$outdata = substr( $contents, 33 );

				$i = strpos( $outdata, "\000" );

				$super->html = substr( $outdata, 0, $i );
				//$super->mathml = substr( $outdata, $i + 1 );
			} elseif ( ( $retval == 'c' ) || ( $retval == 'm' ) || ( $retval == 'l' ) ) {
				$super->html = substr( $contents, 33 );
				if ( $retval == 'c' ) {
					$super->conservativeness = 2;
				} elseif ( $retval == 'm' ) {
					$super->conservativeness = 1;
				} else {
					$super->conservativeness = 0;
				}
				//$super->mathml = null;
			} elseif ( $retval == 'X' ) {
				$super->html = null;
				//$super->mathml = substr( $contents, 33 );
				$super->conservativeness = 0;
			} elseif ( $retval == '+' ) {
				$super->html = null;
				//$super->mathml = null;
				$super->conservativeness = 0;
			} else {
				$errbit = htmlspecialchars( substr( $contents, 1 ) );
				switch( $retval ) {
					case 'E':
						$errmsg = $super->_error( 'math_lexing_error', $errbit );
						break;
					case 'S':
						$errmsg = $super->_error( 'math_syntax_error', $errbit );
						break;
					case 'F':
						$errmsg = $super->_error( 'math_unknown_function', $errbit );
						break;
					default:
						$errmsg = $super->_error( 'math_unknown_error', $errbit );
				}
			}
			
			if ( !$errmsg ) {
				$super->hash = substr( $contents, 1, 32 );
			}

			wfRunHooks( 'MathAfterTexvc', array( &$super, &$errmsg ) );

			if ( $errmsg ) {
				return $errmsg;
			}
		
}