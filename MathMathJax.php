<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Renderer for MathJax
 * @file
 */


/**
 * Takes LaTeX fragments and outputs the source directly to the browser
 *
 * @author Tomasz Wegrzanowski
 * @author Brion Vibber
 * @author Moritz Schubotz
 * @ingroup Parser
 */
class MathMathJax extends MathTexvc {
	function render() {
		$imagePreview = true;
		if ( !$this->readCache() ) { // cache miss
			$result = $this->callTexvc();
			if ( $result != self::MW_TEXVC_SUCCESS ) {
				$imagePreview = false;
			}
		}
		if ($imagePreview) {
			$preview = self::getMathImageHTML();
                } else {
			$preview = $this->getTex();
                }
		return Xml::openElement( 'span',
			$this->getAttributes(
				'span',
				array(
					'class' => 'tex',
					'dir' => 'ltr'
				)
			)).Xml::openElement( 'span',
				array(
					'class' => 'MathJax_Preview'
				)
			).$preview.Xml::closeElement( 'span' ).
			Xml::element( 'script',
				array( 'type' => 'math/tex'),
				str_replace( "\n", " ", $this->getTex() )
			).Xml::closeElement( 'span' );
	}
}
