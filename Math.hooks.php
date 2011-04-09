<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2011 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

class MathHooks {
	/**
	 * Set up $wgMathPath and $wgMathDirectory globals if they're not already
	 * set.
	 */
	static function setup() {
		global $wgMathPath, $wgMathDirectory;
		global $wgUploadPath, $wgUploadDirectory;
		if ( $wgMathPath === false ) {
			$wgMathPath = "{$wgUploadPath}/math";
		}
		if ( $wgMathDirectory === false ) {
			$wgMathDirectory = "{$wgUploadDirectory}/math";
		}
	}

	/**
	 * Register the <math> tag with the Parser.
	 *
	 * @param $parser Object: instance of Parser
	 * @return Boolean: true
	 */
	static function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'math', array( 'MathHooks', 'mathTagHook' ) );
		return true;
	}

	/**
	 * Callback function for the <math> parser hook.
	 *
	 * @param $content
	 * @param $attributes
	 * @param $parser Parser
	 * @return
	 */
	static function mathTagHook( $content, $attributes, $parser ) {
		global $wgContLang;
		$renderedMath = MathRenderer::renderMath(
			$content, $attributes, $parser->getOptions()
		);
		return $wgContLang->armourMath( $renderedMath );
	}

	/**
	 * Add the new math rendering options to Special:Preferences.
	 *
	 * @param $user Object: current User object
	 * @param $defaultPreferences Object: Preferences object
	 * @return Boolean: true
	 */
	static function onGetPreferences( $user, &$defaultPreferences ) {
		global $wgLang;
		$defaultPreferences['math'] = array(
			'type' => 'radio',
			'options' => array_flip( array_map( 'wfMsgHtml', $wgLang->getMathNames() ) ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		);
		return true;
	}
}
