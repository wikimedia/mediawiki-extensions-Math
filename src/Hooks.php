<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use MediaWiki\Config\ConfigException;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Settings\SettingsBuilder;
use MediaWiki\SpecialPage\Hook\SpecialPage_initListHook;

class Hooks implements
	SpecialPage_initListHook
{

	/**
	 * Extension registration callback, used to apply dynamic defaults for configuration variables.
	 */
	public static function onConfig( array $extInfo, SettingsBuilder $settings ) {
		$config = $settings->getConfig();

		// Documentation of MathRestbaseInterface::getUrl() should be updated when this is changed.

		$fullRestbaseUrl = $config->get( 'MathFullRestbaseURL' );
		$internalRestbaseURL = $config->get( 'MathInternalRestbaseURL' );

		if ( !$fullRestbaseUrl ) {
			throw new ConfigException(
				'Math extension can not find Restbase URL. Please specify $wgMathFullRestbaseURL.'
			);
		}

		if ( !$internalRestbaseURL ) {
			// Default to using the external URL for internal calls as well.
			$settings->overrideConfigValue( 'MathInternalRestbaseURL', $fullRestbaseUrl );
		}
	}

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
