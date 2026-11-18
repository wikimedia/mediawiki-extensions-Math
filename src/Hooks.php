<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;

class Hooks implements
	SpecialPage_initListHook
{

	public static function onExtensionFunctions() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			global $wgRestAPIAdditionalRouteFiles;
			$wgRestAPIAdditionalRouteFiles[] = dirname( __DIR__ ) . '/popupRestRoutes.json';
		}
	}

	/**
	 * Remove Special:MathWikibase if the Wikibase client extension isn't loaded
	 *
	 * @param array &$list
	 */
	public function onSpecialPage_initList( &$list ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			unset( $list['MathWikibase'] );
		}
	}

}
