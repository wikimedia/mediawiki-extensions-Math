<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2013 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz, and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * @author Moritz Schubotz
 */
class MathInputCheckTexvc extends MathInputCheck {

	/**
	 * Converts an error returned by texvc to a localized exception
	 *
	 * @param string $texvcResult error result returned by texvc
	 * @param bool|MathRenderer $errorRenderer
	 * @return string
	 */
	public function convertTexvcError( $texvcResult, $errorRenderer = false ) {
		$texvcStatus = substr( $texvcResult, 0, 1 );
		$errDetails = htmlspecialchars( substr( $texvcResult, 1 ) );

		if ( $errorRenderer === false ) {
			$errorRenderer =  new MathSource( $this->inputTeX );
		}

		switch ( $texvcStatus ) {
			case 'E':
				$errMsg = $errorRenderer->getError( 'math_lexing_error' );
				break;
			case 'S':
				$errMsg = $errorRenderer->getError( 'math_syntax_error' );
				break;
			case 'F':
				$errMsg = $errorRenderer->getError( 'math_unknown_function', $errDetails );
				break;
			default:
				$errMsg = $errorRenderer->getError( 'math_unknown_error' );
		}

		return $errMsg;
	}

	/**
	 * @return boolean
	 */
	public function isValid() {
		$us = $this;
		$checkWork = new PoolCounterWorkViaCallback( 'MathTexvc-check', "", array(
			'doWork' => function() use ( $us ) {
				return $us->doValidCheck();
			}
		) );
		return $checkWork->execute();
	}

	/**
	 * @global type $wgTexvc
	 * @return boolean
	 */
	public function doValidCheck() {
		global $wgMathTexvcCheckExecutable;
		if ( !is_executable( $wgMathTexvcCheckExecutable ) ) {
			$msg = wfMessage( 'math_notexvccheck' )->inContentLanguage()->escaped();
			trigger_error( $msg, E_USER_NOTICE );
			wfDebugLog( 'Math', $msg );
			return true;
		}

		$cmd = $wgMathTexvcCheckExecutable . ' ' . wfEscapeShellArg( $this->inputTeX );

		if ( wfIsWindows() ) {
			# Invoke it within cygwin sh, because texvc expects sh features in its default shell
			$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
		}

		wfDebugLog( 'Math', "TeX check command: $cmd\n" );
		$contents = wfShellExec( $cmd );
		wfDebugLog( 'Math', "TeX check result:\n $contents\n---\n" );

		if ( strlen( $contents ) === 0 ) {
			wfDebugLog( 'Math', "TeX check output was empty. \n" );
			$this->lastError = MathRenderer::getError( 'math_unknown_error' );

			return false;
		}

		$retval = substr( $contents, 0, 1 );

		if ( $retval !== '+' ) {
			$this->lastError = $this->convertTexvcError( $contents );
			wfDebugLog( 'Math', 'checkTex failed:' . $this->lastError );

			return false;
		} else {
			$this->validTeX = substr( $contents, 1 );
			$this->isSecure = true;
			wfDebugLog( 'Math', 'checkTex successful tex is now: ' . $this->validTeX );

			return true;
		}
	}

}
