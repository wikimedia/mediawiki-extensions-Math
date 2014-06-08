<?php
/**
 * Description of SpecialMathShowSVG
 *
 * @author Moritz Schubotz (Physikerwelt)
 */
class SpecialMathShowImage extends SpecialPage {
	private $noRender = false;
	private $renderer = null;
	private $mode = false;

	function __construct() {
		parent::__construct( 'MathShowImage' );
	}
	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
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
			$request->response()->header( 'Cache-Control: public max-age=2419200' ); // 4 weeks
			$request->response()->header( 'Vary: User-Agent' );
		}
	}

	function execute( $par ) {
		$request = $this->getRequest();
		$hash = $request->getText( 'hash', '' );
		$tex = $request->getText( 'tex', '');
		$asciimath = $request->getText( 'asciimath', '');
		$this->mode = $request->getInt( 'mode', MW_MATH_MATHML );
		if ( $hash === '' && $tex === '' && $asciimath === '' ) {
			$this->setHeaders( false );
			echo $this->printSvgError( 'No Inputhash specified' );
		} else {
			if ( $tex === '' && $asciimath === ''){
				switch ( $this->mode ) {
				case MW_MATH_PNG:
					$this->renderer = MathTexvc::newFromMd5( $hash );
					break;
				case MW_MATH_LATEXML:
					$this->renderer = MathLaTeXML::newFromMd5( $hash );
					break;
				default:
					$this->renderer = MathMathML::newFromMd5( $hash );
				}
				$this->noRender = $request->getBool( 'noRender', false );
				if ( $this->noRender ) {
					$success = $this->renderer->readFromDatabase();
				} else {
					if ( $this->mode == MW_MATH_PNG ) {
						$mmlRenderer = MathMathML::newFromMd5( $hash );
						$mmlRenderer->readFromDatabase();
						$this->renderer = new MathTexvc( $mmlRenderer->getUserInputTex() );
					}
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
					$output = $this->renderer->getPng();
				} else {
					$output = $this->renderer->getSvg();
				}
			} else {
				// Error message in PNG not supported
				$output = $this->printSvgError( $this->renderer->getLastError() );
			}
			if ( $output == "" ) {
				$output = $this->printSvgError( 'No Output produced' );
				$success = false;
			}
			$this->setHeaders( $success );
			echo $output;
			$this->renderer->writeCache();
		}
	}

	/**
	 * Prints the specified error message as svg.
	 * @param string $msg error message
	 * @return xml svg image with the error message
	 */
	private function printSvgError( $msg ) {
		global $wgMathDebug;
		$result =  '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 100 4"
 preserveAspectRatio="xMidYMid meet" >' .
				'<text text-anchor="start" fill="red" y="2">' . htmlspecialchars( $msg ) . '</text></svg>';
		if ( $wgMathDebug ) {
			$result .= '<!--'. var_export($this->renderer, true) .'-->';
		}
		return $result;
	}

}