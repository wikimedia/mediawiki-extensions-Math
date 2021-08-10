<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

namespace MediaWiki\Extension\Math;

use DatabaseUpdater;
use Exception;
use ExtensionRegistry;
use Maintenance;
use MediaWiki\MediaWikiServices;
use RequestContext;

class Hooks {

	/**
	 * MaintenanceRefreshLinksInit handler; optimize settings for refreshLinks batch job.
	 *
	 * @param Maintenance $maint
	 * @return bool hook return code
	 */
	public static function onMaintenanceRefreshLinksInit( $maint ) {
		$user = RequestContext::getMain()->getUser();

		// Don't parse LaTeX to improve performance
		MediaWikiServices::getInstance()->getUserOptionsManager()
			->setOption( $user, 'math', MathConfig::MODE_SOURCE );
		return true;
	}

	/**
	 * LoadExtensionSchemaUpdates handler; set up math table on install/upgrade.
	 *
	 * @param DatabaseUpdater $updater
	 * @throws Exception
	 * @return bool
	 */
	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		// This hook runs before services are initialized, so we cannot
		// use MathConfig service directly.
		global $wgMathValidModes;

		$type = $updater->getDB()->getType();
		$validModes = array_map( static function ( $mode ) {
			return MathConfig::normalizeRenderingMode( $mode );
		}, $wgMathValidModes );
		if ( in_array( MathConfig::MODE_LATEXML, $validModes ) ) {
			if ( in_array( $type, [ 'mysql', 'sqlite', 'postgres' ] ) ) {
				$sql = __DIR__ . '/../db/mathlatexml.' . $type . '.sql';
				$updater->addExtensionTable( 'mathlatexml', $sql );
				if ( $type == 'mysql' ) {
					$sql = __DIR__ . '/../db/patches/mathlatexml.mathml-length-adjustment.mysql.sql';
					$updater->modifyExtensionField( 'mathlatexml', 'math_mathml', $sql );
				}
			} else {
				throw new Exception( "Math extension does not currently support $type database for LaTeXML." );
			}
		}
		if ( in_array( MathConfig::MODE_MATHML, $validModes ) ) {
			if ( in_array( $type, [ 'mysql', 'sqlite', 'postgres' ] ) ) {
				$sql = __DIR__ . '/../db/mathoid.' . $type . '.sql';
				$updater->addExtensionTable( 'mathoid', $sql );
				if ( $type == 'mysql' ) {
					$sql = __DIR__ . '/../db/patches/mathoid.add_png.mysql.sql';
					$updater->addExtensionField( 'mathoid', 'math_png', $sql );
				}
			} else {
				throw new Exception( "Math extension does not currently support $type database for Mathoid." );
			}
		}

		return true;
	}

	/**
	 * Remove Special:MathWikibase if the Wikibase client extension isn't loaded
	 *
	 * @param array &$list
	 * @return bool true
	 */
	public static function onSpecialPageInitList( &$list ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'WikibaseClient' ) ) {
			unset( $list['MathWikibase'] );
		}
		return true;
	}

}

class_alias( Hooks::class, 'MathHooks' );
