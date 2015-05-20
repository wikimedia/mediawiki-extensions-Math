<?php
/**
 * Description of SpecialMathShowSVG
 *
 * @author Moritz Schubotz (Physikerwelt)
 */
class SpecialMathShowImage extends SpecialPage {
	private $noRender = false;
	private $renderer = null;
	private $mode = MW_MATH_MATHML;

	function __construct() {
		parent::__construct(
			'MathShowImage',
			'', // Don't restrict
			false // Don't show on Special:SpecialPages - it's not useful interactively
		);
	}

	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 * @param bool $success
	 */
	function setHeaders( $success = true ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$out->setArticleBodyOnly( true );
		$out->setArticleRelated( false );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->disable();
		if ( $success && $this->mode == MW_MATH_PNG ) {
			$request->response()->header( "Content-type: image/png;" );
		} else {
			$request->response()->header( "Content-type: image/svg+xml; charset=utf-8" );
		}
		if ( $success && !( $this->noRender ) ) {
			$request->response()->header( 'Cache-Control: public, s-maxage=604800, max-age=3600' ); // 1 week (server) 1 hour (client)
			$request->response()->header( 'Vary: User-Agent' );
		}
	}

	function execute( $par ) {
		global $wgMathValidModes, $wgMathEnableExperimentalInputFormats;
		$request = $this->getRequest();
		$hash = $request->getText( 'hash', '' );
		$tex = $request->getText( 'tex', '' );
		if ( $wgMathEnableExperimentalInputFormats ) {
			$asciimath = $request->getText( 'asciimath', '' );
		} else {
			$asciimath = '';
		}
		$this->mode = $request->getInt( 'mode', MW_MATH_MATHML );
		if ( !in_array( $this->mode, $wgMathValidModes ) ) {
			// Fallback to the default if an invalid mode was specified
			$this->mode = MW_MATH_MATHML;
		}
		if ( $hash === '' && $tex === '' && $asciimath === '' ) {
			$this->setHeaders( false );
			echo $this->printSvgError( 'No Inputhash specified' );
		} else {
			if ( $tex === '' && $asciimath === ''){
				switch ( $this->mode ) {
					case MW_MATH_LATEXML:
						$this->renderer = MathLaTeXML::newFromMd5( $hash );
						break;
					default:
						$this->renderer = MathMathML::newFromMd5( $hash );
				}
				$this->noRender = $request->getBool( 'noRender', false );
				$isInDatabase = $this->renderer->readFromDatabase();
				if ( $isInDatabase || $this->noRender ) {
					$success = $isInDatabase;
				} else {
					$success = $this->renderer->render();
				}
			} elseif ( $asciimath === '' ) {
				$this->renderer = MathRenderer::getRenderer( $tex , array(), $this->mode );
				$success = $this->renderer->render();
			} else {
				$this->renderer = MathRenderer::getRenderer( $asciimath , array( 'type' => 'ascii' ), $this->mode );
				$success = $this->renderer->render();
			}
			if ( $success ) {
				if ( $this->mode == MW_MATH_PNG ) {
					$prefix = 'data:image/png;base64,';
					$img = $this->renderer->getPng();
					if( substr( $img, 0, strlen( $prefix ) ) === $prefix ){
						$img = str_replace( $prefix, '', $img );
						$img = str_replace( ' ', '+', $img );
						$output = base64_decode( $img );
					} else {
						$output = $this->printSvgError( 'invalid png file' );
						$success = false;
					}
				} else {
					$output = $this->renderer->getSvg();
				}
			} else {
				// Error message in PNG not supported
				$output = $this->printSvgError( $this->renderer->getLastError() );
			}
			if ( $output == "" || $output == false ) {
				$output = $this->printSvgError( 'No Output produced' );
				$success = false;
			}
			$this->setHeaders( $success );
			echo $output;
			if ( $success ){
				$this->renderer->writeCache();
			}
		}
	}

	/**
	 * Prints the specified error message as svg.
	 * @param string $msg error message
	 * @return xml svg image with the error message
	 */
	private function printSvgError( $msg ) {
		global $wgMathDebug;
		$result =  '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 4"
 preserveAspectRatio="xMidYMid meet" >' .
			'<text text-anchor="start" fill="red" y="2">' . htmlspecialchars( $msg ) . '</text></svg>';
		if ( $wgMathDebug ) {
			$result .= '<!--'. var_export($this->renderer, true) .'-->';
		}
		return $result;
	}

	protected function getGroupName() {
		return 'other';
	}
}
