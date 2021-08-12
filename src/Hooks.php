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
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RequestContext;
use User;

class Hooks {

	private const MATHCACHEKEY = 'math=';

	public static function mathModeToHashKey( $mode, $default = 0 ) {
		$defs = [
			'png'    => 0,
			'source' => 3,
			'mathml' => 5,
			'latexml' => 7 ];

		if ( array_key_exists( $mode, $defs ) ) {
			return $defs[$mode];
		} else {
			return $default;
		}
	}

	/**
	 * Generate a user dependent hash cache key.
	 * The hash key depends on the rendering mode.
	 * @param string &$confstr The to-be-hashed key string that is being constructed
	 * @param User $user reference to the current user
	 * @param array &$forOptions userOptions used on that page
	 * @return true
	 */
	public static function onPageRenderingHash( &$confstr, $user, &$forOptions = [] ) {
		// To be independent of the MediaWiki core version,
		// we check if the core caching logic for math is still available.
		// TODO this check shouldn't be needed anymore, since none of the versions of MediaWiki
		// core that this extension supports have the method.
		if ( !is_callable( 'ParserOptions::getMath' ) && in_array( 'math', $forOptions ) ) {
			$mathString = MathConfig::normalizeRenderingMode( $user->getOption( 'math' ) );
			$mathOption = self::mathModeToHashKey( $mathString, 0 );
			// Check if the key already contains the math option part
			if (
				!preg_match(
					'/(^|!)' . self::MATHCACHEKEY . $mathOption . '(!|$)/',
					$confstr
				)
			) {
				// The math part of cache key starts with "math="
				// followed by a star or a number for the math mode
				if ( preg_match( '/(^|!)' . self::MATHCACHEKEY . '[*\d]m?(!|$)/', $confstr ) ) {
					$confstr = preg_replace(
						'/(^|!)' . self::MATHCACHEKEY . '[*\d]m?(!|$)/',
						'\1' . self::MATHCACHEKEY . $mathOption . '\2',
						$confstr
					);
				} else {
					$confstr .= '!' . self::MATHCACHEKEY . $mathOption;
				}

				LoggerFactory::getInstance( 'Math' )->debug( "New cache key: $confstr" );
			} else {
				LoggerFactory::getInstance( 'Math' )->debug( "Cache key found: $confstr" );
			}
		}

		return true;
	}

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
