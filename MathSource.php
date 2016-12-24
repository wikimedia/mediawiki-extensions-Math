<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2012 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz and other MediaWiki contributors
 * GPLv2 license; info in main package.
 *
 * Contains everything related to <math> </math> parsing
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
class MathSource extends MathRenderer {
	/**
	 * @param string $tex
	 * @param array $params
	 */
	function __construct( $tex = '', $params = [] ) {
		parent::__construct( $tex, $params );
		$this->setMode( 'source' );
	}

	/**
	 * Renders TeX by outputting it to the browser in a span tag
	 *
	 * @return string span tag with TeX
	 */
	function getHtmlOutput() {
		# No need to render or parse anything more!
		# New lines are replaced with spaces, which avoids confusing our parser (bugs 23190, 22818)
		if ( $this->getMathStyle() == 'display' ) {
			$class = 'mwe-math-fallback-source-display';
		} else {
			$class = 'mwe-math-fallback-source-inline';
		}
		return Xml::element( 'span',
			$this->getAttributes(
				'span',
				[
					// the former class name was 'tex'
					// for backwards compatibility we keep this classname
					'class' => $class. ' tex',
					'dir' => 'ltr'
				]
			),
			'$ ' . str_replace( "\n", " ", $this->getTex() ) . ' $'
		);
	}

	protected function getMathTableName() {
		throw new Exception( 'in math source mode no database caching should happen' );
	}

	/**
	 * No rendering required in plain text mode
	 * @return boolean
	 */
	function render() {
		// assume unchanged to avoid unnecessary database access
		$this->changed = false;
		return true;
	}
}
