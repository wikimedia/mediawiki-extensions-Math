<?php
/**
 * MediaWiki math extension
 *
 * (c) 2002-2011 various MediaWiki contributors
 * GPLv2 license; info in main package.
 */

class MathHooks {
	static function setup() {
		global $wgMathPath, $wgMathDirectory;
		global $wgUploadPath, $wgUploadDirectory;
		if ( $wgMathPath === false ) $wgMathPath = "{$wgUploadPath}/math";
		if ( $wgMathDirectory === false ) $wgMathDirectory = "{$wgUploadDirectory}/math";
	}

	static function onParserFirstCallInit($parser)
	{
		$parser->setHook( 'math', array( 'MathHooks', 'mathTagHook' ) );
		return true;
	}

	/**
	 * @param  $content
	 * @param  $attributes
	 * @param $parser Parser
	 * @return
	 */
	static function mathTagHook( $content, $attributes, $parser ) {
		global $wgContLang;
		return $wgContLang->armourMath( MathRenderer::renderMath( $content, $attributes, $parser->getOptions() ) );
	}

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
