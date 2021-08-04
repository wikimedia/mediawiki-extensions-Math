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

	public static function mathConstantToString( $value, array $defs, $prefix, $default ) {
		foreach ( $defs as $defKey => $defValue ) {
			if ( !defined( $defKey ) ) {
				define( $defKey, $defValue );
			} elseif ( $defValue !== constant( $defKey ) ) {
				throw new Exception( 'Math constant "' . $defKey . '" has unexpected value "' .
					constant( $defKey ) . '" instead of "' . $defValue );
			}
		}
		$invDefs = array_flip( $defs );
		if ( is_int( $value ) ) {
			if ( array_key_exists( $value, $invDefs ) ) {
				$value = $invDefs[$value];
			} else {
				return $default;
			}
		}
		if ( is_string( $value ) ) {
			$newValues = [];
			foreach ( $defs as $k => $v ) {
				$newValues[$k] = preg_replace_callback( '/_(.)/', static function ( $matches ) {
					return strtoupper( $matches[1] );
				}, strtolower( substr( $k, strlen( $prefix ) ) ) );
			}
			if ( array_key_exists( $value, $defs ) ) {
				return $newValues[$value];
			} elseif ( in_array( $value, $newValues ) ) {
				return $value;
			}
		}
		return $default;
	}

	public static function mathStyleToString( $style, $default = 'inlineDisplaystyle' ) {
		$defs = [
			'MW_MATHSTYLE_INLINE_DISPLAYSTYLE'  => 0, // default large operator inline
			'MW_MATHSTYLE_DISPLAY'              => 1, // large operators centered in a new line
			'MW_MATHSTYLE_INLINE'               => 2, // small operators inline
			'MW_MATHSTYLE_LINEBREAK'            => 3, // break long lines (experimental)
		];
		return self::mathConstantToString( $style, $defs, 'MW_MATHSTYLE_', $default );
	}

	public static function mathCheckToString( $style, $default = 'always' ) {
		$defs = [
			'MW_MATH_CHECK_ALWAYS' => 0,
			'MW_MATH_CHECK_NEVER'  => 1,
			'MW_MATH_CHECK_NEW'    => 2,
		];
		return self::mathConstantToString( $style, $defs, 'MW_MATH_CHECK_', $default );
	}

	public static function mathModeToString( $mode, $default = 'png' ) {
		// The following deprecated modes have been removed:
		// 'MW_MATH_SIMPLE'      => 1
		// 'MW_MATH_HTML'        => 2
		// 'MW_MATH_MODERN'      => 4
		// 'MW_MATH_MATHJAX'     => 6
		// 'MW_MATH_LATEXML_JAX' => 8

		$defs = [
			'MW_MATH_PNG'    => 0,
			'MW_MATH_SOURCE' => 3,
			'MW_MATH_MATHML' => 5,
			'MW_MATH_LATEXML' => 7 ];

		return self::mathConstantToString( $mode, $defs, 'MW_MATH_', $default );
	}

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
			$mathString = self::mathModeToString( $user->getOption( 'math' ) );
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
	 * Add the new math rendering options to Special:Preferences.
	 *
	 * @param User $user current User object
	 * @param array &$defaultPreferences Preferences array
	 * @return bool true
	 */
	public static function onGetPreferences( $user, &$defaultPreferences ) {
		global $wgDefaultUserOptions;
		$defaultPreferences['math'] = [
			'type' => 'radio',
			'options' => array_flip( self::getMathNames() ),
			'label' => '&#160;',
			'section' => 'rendering/math',
		];
		// If the default option is not in the valid options the
		// user interface throws an exception (BUG 64844)
		$mode = self::mathModeToString( $wgDefaultUserOptions['math'] );
		$validModes = MathRenderer::getValidModes();
		if ( !in_array( $mode, $validModes ) ) {
			LoggerFactory::getInstance( 'Math' )->error( 'Misconfiguration: ' .
				"\$wgDefaultUserOptions['math'] is not in [ " . implode( ', ', $validModes ) . " ].\n" .
				"Please check your LocalSettings.php file."
			);
			// Display the checkbox in the first option.
			$wgDefaultUserOptions['math'] = $validModes[0];
		}
		return true;
	}

	/**
	 * List of message keys for the various math output settings.
	 *
	 * @return string[]
	 */
	public static function getMathNames() {
		$names = [];
		foreach ( MathRenderer::getValidModes() as $mode ) {
			$names[$mode] = wfMessage( 'mw_math_' . $mode )->escaped();
		}

		return $names;
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
			->setOption( $user, 'math', 'source' );
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
		$type = $updater->getDB()->getType();

		if ( in_array( 'latexml', MathRenderer::getValidModes() ) ) {
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
		if ( in_array( 'mathml', MathRenderer::getValidModes() ) ) {
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
