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
	const SEPARATOR = "SVGOUTPUT:";
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
			$result = $this->renderSvg();
		}
		return $this->doHTMLRender();
	}

	private function getMathImageUrl(){
		return SpecialPage::getTitleFor('MathShowSvg')->getLocalURL()."?hash=".  md5($this->getTex());
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

	/**
	 * Does the actual call to texvc
	 *
	 * @return int|string MW_TEXVC_SUCCESS or error string
	 */
	public function renderSvg() {
		global $wgTexvc;
		$this->setHash( bin2hex( $this->getInputHash() ) );
//		$tmpDir = $this->getHashPath();
		$texout = $this->getLaTeXHeader() . '$$' . $this->getTex() . '$$'.
			'\end{document}';
//		$file = file_put_contents($tmpDir .'/' .$this->getHash().'.tex',$texout);
//		echo file_get_contents($tmpDir .'/' .$this->getHash().'.tex');
		$wgTexvc = '/vagrant/mediawiki/extensions/Math/math/tex2svg';
		if ( !is_executable( $wgTexvc ) ) {
			return $this->getError( 'math_notexvc' );
		}
		$cmd = $wgTexvc . ' ' . wfEscapeShellArg( $texout);
		if ( wfIsWindows() ) {
			# Invoke it within cygwin sh, because texvc expects sh features in its default shell
			$cmd = 'sh -c ' . wfEscapeShellArg( $cmd );
		}
		wfDebugLog( 'Math', "TeX: $cmd\n" );
		$contents = wfShellExec( $cmd );
		wfDebugLog( 'Math', "TeX output:\n $contents\n---\n" );
		$svgpos = strpos( $contents, self::SEPARATOR);
		if($svgpos === false){
			$consoleOutput = $contents;
			$this->lastError = $this->getError('math_unknown_error');
			return false;
		} else {
			$consoleOutput = substr( $contents , 0 ,$svgpos );
			$this->svg = substr($contents, $svgpos + strlen(self::SEPARATOR)+1 );
			$this->writeToDatabase();
		}
		if ( strlen( $contents ) == 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Does the HTML rendering
	 *
	 * @return string HTML string
	 */
	public function doHTMLRender() {
		return $this->getMathImageHTML();
	}

}