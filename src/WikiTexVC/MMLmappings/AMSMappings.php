<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

/**
 * Based on AMSMappings.js in MML3
 * Only importing infix atm
 * Singleton
 *
 */
class AMSMappings {

	/** @var self|null */
	private static $instance = null;

	private function __construct() {
		// Just an empty private constructor, for singleton pattern
	}

	public static function getInstance(): self {
		self::$instance ??= new AMSMappings();
		return self::$instance;
	}

	public static function getMacroByKey( string $key ) {
		$rendering = TexUtil::getInstance()->macro_rendering( trim( $key ) );
		return $rendering !== false ? $rendering : null;
	}

	public static function getEnvironmentByKey( string $key ) {
		$rendering = TexUtil::getInstance()->environment_rendering( trim( $key ) );
		return $rendering !== false ? $rendering : null;
	}
}
