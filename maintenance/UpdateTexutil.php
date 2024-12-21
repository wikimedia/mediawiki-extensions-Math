<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;

class UpdateTexutil extends Maintenance {

	private const LEGACY_CONCEPTS = [
		"digamma" => '\u03DD',
		"varkappa" => '\u03F0',
		"varGamma" => [ '\u0393', [ "mathvariant" => Variants::ITALIC ] ],
		"varDelta" => [ '\u0394', [ "mathvariant" => Variants::ITALIC ] ],
		"varTheta" => [ '\u0398', [ "mathvariant" => Variants::ITALIC ] ],
		"varLambda" => [ '\u039B', [ "mathvariant" => Variants::ITALIC ] ],
		"varXi" => [ '\u039E', [ "mathvariant" => Variants::ITALIC ] ],
		"varPi" => [ '\u03A0', [ "mathvariant" => Variants::ITALIC ] ],
		"varSigma" => [ '\u03A3', [ "mathvariant" => Variants::ITALIC ] ],
		"varStigma" => [ '\u03DB', [ "mathvariant" => Variants::ITALIC ] ],
		"varUpsilon" => [ '\u03A5', [ "mathvariant" => Variants::ITALIC ] ],
		"varPhi" => [ '\u03A6', [ "mathvariant" => Variants::ITALIC ] ],
		"varPsi" => [ '\u03A8', [ "mathvariant" => Variants::ITALIC ] ],
		"varOmega" => [ '\u03A9', [ "mathvariant" => Variants::ITALIC ] ],
		"beth" => '\u2136',
		"gimel" => '\u2137',
		"daleth" => '\u2138',
		"backprime" => [ '\u2035', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"hslash" => '\u210F',
		"varnothing" => [ '\u2205', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"blacktriangle" => '\u25B4',
		"triangledown" => [ '\u25BD', [ "variantForm" => "True" ] ], // actually: "variantForm" => "True"
		"blacktriangledown" => '\u25BE',
		"square" => '\u25FB',
		"Box" => '\u25FB',
		"blacksquare" => '\u25FC',
		"lozenge" => '\u25CA',
		"Diamond" => '\u25CA',
		"blacklozenge" => '\u29EB',
		"circledS" => [ '\u24C8', [ "mathvariant" => Variants::NORMAL ] ],
		"bigstar" => '\u2605',
		"sphericalangle" => '\u2222',
		"measuredangle" => '\u2221',
		"nexists" => '\u2204',
		"complement" => '\u2201',
		"mho" => '\u2127',
		"eth" => [ '\u00F0', [ "mathvariant" => Variants::NORMAL ] ],
		"Finv" => '\u2132',
		"diagup" => '\u2571',
		"Game" => '\u2141',
		"diagdown" => '\u2572',
		"Bbbk" => [ '\u006B', [ "mathvariant" => Variants::DOUBLESTRUCK ] ],
		"yen" => '\u00A5',
		"circledR" => '\u00AE',
		"checkmark" => '\u2713',
		"maltese" => '\u2720'
	];

	public function execute() {
		$jsonFilePath = './src/WikiTexVC/texutil.json';

		$jsonContent = json_decode( file_get_contents( $jsonFilePath ), true );

		if ( $jsonContent === null ) {
			die( "Failed to decode texutil.json. Please check the file format.\n" );
		}

		foreach ( self::LEGACY_CONCEPTS as $key => $value ) {
			$identifierEntry = [];

			// Check how to handle the value type (string or array)
			if ( is_string( $value ) ) {
				$identifierEntry[] = $value; // Just the Unicode character
			} elseif ( is_array( $value ) ) {
				$identifierEntry[] = $value[0]; // Unicode character
				if ( !empty( $value[1] ) ) {
					// this converts PHP constants to JSON strings
					$identifierEntry[] = $value[1]; // Additional attributes
				}
			}

			// Check if the entry already exists in texutil.json
			if ( isset( $jsonContent["\\$key"] ) ) {
				// Preserve existing attributes and only add or update the identifier
				$jsonContent["\\$key"]['identifier'] = $identifierEntry;
			} else {
				// Create a new entry if it doesn't exist
				$jsonContent["\\$key"] = [
					"identifier" => $identifierEntry
				];
			}

			// Sort the entry alphabetically
			ksort( $jsonContent["\\$key"] );
		}

		$jsonString = json_encode( $jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

		$jsonStringWithTabs = preg_replace_callback( '/^( +)/m', static function ( $matches ) {
				// Convert spaces to tabs (assuming 4 spaces per tab level)
				return str_repeat( "\t", strlen( $matches[1] ) / 4 );
		}, $jsonString ) . "\n";

		file_put_contents( $jsonFilePath, $jsonStringWithTabs );

		echo "texutil.json successfully updated.\n";
	}
}
$maintClass = UpdateTexutil::class;
require_once RUN_MAINTENANCE_IF_MAIN;
