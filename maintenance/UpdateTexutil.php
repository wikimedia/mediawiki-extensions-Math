<?php

// @codeCoverageIgnoreStart
require_once __DIR__ . '/../../../maintenance/Maintenance.php';
// @codeCoverageIgnoreEnd
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;
use MediaWiki\Maintenance\Maintenance;

class UpdateTexutil extends Maintenance {
	private const GROUP = 'operator_rendering';

	public function execute() {
		$jsonFilePath = './src/WikiTexVC/texutil.json';

		$jsonContent = json_decode( file_get_contents( $jsonFilePath ), true );

		if ( $jsonContent === null ) {
			die( "Failed to decode texutil.json. Please check the file format.\n" );
		}
		$tu = TexUtil::getInstance();
		foreach ( $tu->getBaseElements()[self::GROUP] as $key => $value ) {
			if ( !preg_match( "/&#x([0-9A-F]+);/", $value[0], $chr ) ) {
				continue;
			}
			$jsonContent["$key"][self::GROUP][0] = mb_chr( hexdec( ( $chr[1] ) ), 'UTF-8' );

			// Sort the entry alphabetically
			ksort( $jsonContent["$key"] );
		}
		// Sort the entire file
		ksort( $jsonContent );
		$jsonString = json_encode( $jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		$jsonStringWithTabs = preg_replace_callback( '/^( +)/m', static function ( $matches ) {
				// Convert spaces to tabs (assuming 4 spaces per tab level)
				return str_repeat( "\t", strlen( $matches[1] ) / 4 );
		}, $jsonString ) . "\n";
		// prevent eslint error  Unnecessary escape character: \/  no-useless-escape
		$jsonStringWithTabs = str_replace( '\/', '/', $jsonStringWithTabs );

		file_put_contents( $jsonFilePath, $jsonStringWithTabs );

		echo "texutil.json successfully updated.\n";
	}
}
// @codeCoverageIgnoreStart
$maintClass = UpdateTexutil::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
