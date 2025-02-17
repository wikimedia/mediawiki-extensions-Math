<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

class UpdateTexutil extends Maintenance {

	private const LEGACY_CONCEPTS = [
		"ce" => [ 'machine', 'ce' ],
		"pu" => [ 'machine', 'pu' ],
		"longrightleftharpoons" => [
			'macro',
			'\\stackrel{\\textstyle{-}\\!\\!{\\rightharpoonup}}{\\smash{{\\leftharpoondown}\\!\\!{-}}}'
		],
		"longRightleftharpoons" => [
			'macro',
			'\\stackrel{\\textstyle{-}\\!\\!{\\rightharpoonup}}{\\smash{\\leftharpoondown}}'
		],
		"longLeftrightharpoons" => [
			'macro',
			'\\stackrel{\\textstyle\\vphantom{{-}}{\\rightharpoonup}}{\\smash{{\\leftharpoondown}\\!\\!{-}}}'
		],
		"longleftrightarrows" => [
			'macro',
			'\\stackrel{\\longrightarrow}{\\smash{\\longleftarrow}\\Rule{0px}{.25em}{0px}}'
		],
		"tripledash" => [
			'macro',
			'\\vphantom{-}\\raise{2mu}\\\kern{2mu}\\tiny\\text{-}\\kern{1mu}\\text{-}\\kern{1mu}\\text{-}\\kern{2mu}}'
		],
		"xleftrightarrow" => [ 'xArrow', 0x2194, 6, 6 ],
		"xrightleftharpoons" => [ 'xArrow', 0x21CC, 5, 7 ],
		"xRightleftharpoons" => [ 'xArrow', 0x21CC, 5, 7 ],
		"xLeftrightharpoons" => [ 'xArrow', 0x21CC, 5, 7 ],

		"bond" => [ "chemCustom", "\\bond" ],
	];

	public function execute() {
		$jsonFilePath = './src/WikiTexVC/texutil.json';

		$jsonContent = json_decode( file_get_contents( $jsonFilePath ), true );

		if ( $jsonContent === null ) {
			die( "Failed to decode texutil.json. Please check the file format.\n" );
		}

		foreach ( self::LEGACY_CONCEPTS as $key => $value ) {
			 $key = '\\' . $key;

			// Check how to handle the value type (string or array)
			// as we don't have strings in the current value of LEGACY_CONCEPTS,
			// phab complains, that we don't need this check. Thus, I commented it out.
			// if ( is_string( $value ) ) {
			//	$value = [ $value ];
			//}
			$value[0] = MMLutil::uc2xNotation( $value[0] );
			// Remove the texClass from the array
			// unset( $value[1]['texClass'] );

			// Check if the entry already exists in texutil.json
			if ( isset( $jsonContent["$key"] ) ) {
				// Preserve existing attributes and only add or update the identifier
				$jsonContent["$key"]['callback'] = $value;
			} else {
				// Create a new entry if it doesn't exist
				$jsonContent["$key"] = [
					'callback' => $value
				];
			}

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
$maintClass = UpdateTexutil::class;
require_once RUN_MAINTENANCE_IF_MAIN;
