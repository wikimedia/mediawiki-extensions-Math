<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use ConfigException;
use DatabaseUpdater;
use Exception;
use ExtensionRegistry;
use Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Settings\SettingsBuilder;
use RequestContext;

class Hooks {

	/**
	 * Extension registration callback, used to apply dynamic defaults for configuration variables.
	 */
	public static function onConfig( array $extInfo, SettingsBuilder $settings ) {
		$config = $settings->getConfig();

		$fullRestbaseUrl = $config->get( 'MathFullRestbaseURL' );
		$internalRestbaseURL = $config->get( 'MathInternalRestbaseURL' );
		$useInternalRestbasePath = $config->get( 'MathUseInternalRestbasePath' );
		$virtualRestConfig = $config->get( 'VirtualRestConfig' );

		if ( !$fullRestbaseUrl ) {
			if ( $config->has( 'VisualEditorFullRestbaseURL' ) ) {
				$settings->warning( "MathFullRestbaseURL is falling back to using VisualEditorFullRestbaseURL. " .
					"Please configure the Mathoid API URL explicitly." );

				$fullRestbaseUrl = $config->get( 'VisualEditorFullRestbaseURL' );
				$settings->overrideConfigValue( 'MathFullRestbaseURL', $fullRestbaseUrl );
			} else {
				throw new ConfigException(
					'Math extension can not find Restbase URL. Please specify $wgMathFullRestbaseURL.'
				);
			}
		}

		if ( !$useInternalRestbasePath ) {
			if ( $internalRestbaseURL ) {
				$settings->warning( "The MathInternalRestbaseURL setting will be ignored " .
					"because MathUseInternalRestbasePath is set to false." );
			}

			// Force the use of the external URL for internal calls as well.
			$settings->overrideConfigValue( 'MathInternalRestbaseURL', $fullRestbaseUrl );
		} elseif ( !$internalRestbaseURL ) {
			if ( isset( $virtualRestConfig['modules']['restbase'] ) ) {
				$settings->warning( "The MathInternalRestbaseURL is falling back to " .
					"VirtualRestConfig. Please set MathInternalRestbaseURL explicitly." );

				$restBaseUrl = $virtualRestConfig['modules']['restbase']['url'];
				$restBaseUrl = rtrim( $restBaseUrl, '/' );

				$restBaseDomain = $virtualRestConfig['modules']['restbase']['domain'] ?? 'localhost';

				// Ensure the correct domain format: strip protocol, port,
				// and trailing slash if present.  This lets us use
				// $wgCanonicalServer as a default value, which is very convenient.
				// XXX: This was copied from RestbaseVirtualRESTService. Use UrlUtils::parse instead?
				$restBaseDomain = preg_replace(
					'/^((https?:)?\/\/)?([^\/:]+?)(:\d+)?\/?$/',
					'$3',
					$restBaseDomain
				);

				$internalRestbaseURL = "$restBaseUrl/$restBaseDomain/v1/";
			} else {
				// Default to using the external URL for internal calls as well.
				$internalRestbaseURL = $fullRestbaseUrl;
			}

			$settings->overrideConfigValue( 'MathInternalRestbaseURL', $internalRestbaseURL );
		}
	}

	/**
	 * MaintenanceRefreshLinksInit handler; optimize settings for refreshLinks batch job.
	 *
	 * @param Maintenance $maint
	 */
	public static function onMaintenanceRefreshLinksInit( $maint ) {
		$user = RequestContext::getMain()->getUser();

		// Don't parse LaTeX to improve performance
		MediaWikiServices::getInstance()->getUserOptionsManager()
			->setOption( $user, 'math', MathConfig::MODE_SOURCE );
	}

	/**
	 * LoadExtensionSchemaUpdates handler; set up math table on install/upgrade.
	 *
	 * @param DatabaseUpdater $updater
	 * @throws Exception
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$type = $updater->getDB()->getType();
		if ( !in_array( $type, [ 'mysql', 'sqlite', 'postgres' ] ) ) {
			throw new Exception( "Math extension does not currently support $type database." );
		}

		foreach ( [ 'mathoid', 'mathlatexml' ] as $mode ) {
			$updater->addExtensionTable(
				$mode,
				__DIR__ . "/../sql/$type/$mode.sql"
			);
		}

		if ( $type === 'mysql' ) {
			$updater->addExtensionField(
				'mathoid',
				'math_png',
				__DIR__ . '/../sql/' . $type . '/patch-mathoid.add_png.sql'
			);
		}
	}

	/**
	 * Remove Special:MathWikibase if the Wikibase client extension isn't loaded
	 *
	 * @param array &$list
	 */
	public static function onSpecialPageInitList( &$list ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			unset( $list['MathWikibase'] );
		}
	}

}
