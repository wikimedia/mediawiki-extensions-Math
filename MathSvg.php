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
class MathSvg extends MathRenderer {
	const SEPARATOR = "<!--seperator-->";
	/**
	 * Renders TeX using texvc
	 *
	 * @return string rendered TeK
	 */
	public function render() {
		if ( !$this->readFromDatabase() ) { // cache miss
			wfDebug('Math', 'cache miss. svg renderering called');
		}
		if (! $this->svg){
			$this->renderSvg();
		}
		return $this->getMathImageHTML();
	}

	private function getMathImageUrl(){
		return SpecialPage::getTitleFor('MathShowImage')->getLocalURL(array('hash'=>$this->getMd5()));
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
	 * Does the actual call to shell
	 *
	 * @return int|string MW_TEXVC_SUCCESS or error string
	 */
	public function renderSvg() {
		global $wgTex2Svg;
		if ( !is_executable( $wgTex2Svg ) ) {
			//TODO: Change error message
			$this->lastError = $this->getError( 'math_notexvc' );
			return false;
		}
		$texout = $this->getLaTeXHeader() . '$$' . $this->getTex() . '$$'.
			'\end{document}';
		$cmd = $wgTex2Svg . ' ' . wfEscapeShellArg( $texout).' '. wfEscapeShellArg( $this->getTex());
		if ( wfIsWindows() ) {
			# Invoke it within cygwin sh, because texvc expects sh features in its default shell
			$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
		}
		wfDebugLog( 'Math', "Compiling: $cmd\n" );
		//wfShellExec does not feed back stder
		$contents = wfShellExec( $cmd );
		wfDebugLog( 'Math', "finished" );
		$parts = explode(self::SEPARATOR,$contents);
		if( !is_array($parts) ){
			$this->lastError = $this->getError('math_unknown_error');
			wfDebugLog( 'Math', "Error while converting tex to svg".  htmlspecialchars($contents) );
			var_export($contents);
			return false;
		} else {
			$pngout = $parts[0];
			$svgout = $parts[1];
			$mathml = $parts[2];
			//$consoleOutput = substr( $contents , 0 ,$svgpos );
			wfDebugLog( 'Math', "Conversion was successful" );
			$this->setSvg( $svgout);
			$this->setPng( $pngout);
			$this->setMathml( $mathml);
		}

		return true;
	}

	private function getLaTeXHeader(){
		return '
\documentclass[12pt]{article}

\usepackage{ucs}
\usepackage[utf8]{inputenc}

\nonstopmode

\usepackage{amsmath}
\usepackage{amsfonts}
\usepackage{amssymb}
\usepackage[dvips,usenames]{color}
\usepackage[greek]{babel}
\usepackage{teubner}
\usepackage{eurosym}
\usepackage{cancel}

\pagestyle{empty}
\begin{document}';
	}

}