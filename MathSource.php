<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains everything related to <math> </math> parsing
 * @file
 * @ingroup Parser
 */


/**
 * Takes LaTeX fragments and outputs the source directly to the browser
 *
 * @author Tomasz Wegrzanowski, with additions by Brion Vibber (2003, 2004)
 * @ingroup Parser
 */
class MathSource extends MathRenderer {

	function render($purge=false) {
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

}
