<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

class UpdateTexutil extends Maintenance {

	private const LEGACY_CONCEPTS = [
		'(' => [ '(', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		')' => [ ')', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		'[' => [ '[', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		']' => [ ']', [ "stretchy" => "false" ] ], // added this additionally for running all tc
		'<' => '\u27E8',
		'>' => '\u27E9',
		'\\lt' => '\u27E8',
		'\\gt' => '\u27E9',
		'/' => '/',
		'|' => [ '|', [ "texClass" => TexClass::ORD ] ],
		'.' => '',
		'\\\\' => '\\',
		'\\lmoustache' => '\u23B0',
		'\\rmoustache' => '\u23B1',
		'\\lgroup' => '\u27EE',
		'\\rgroup' => '\u27EF',
		'\\arrowvert' => '\u23D0',
		'\\Arrowvert' => '\u2016',
		'\\bracevert' => '\u23AA',
		'\\Vert' => [ '\u2016', [ "texClass" => TexClass::ORD ] ],
		'\\|' => [ '\u2016', [ "texClass" => TexClass::ORD ] ],
		'\\vert' => [ '|', [ "texClass" => TexClass::ORD ] ],
		'\\uparrow' => '\u2191',
		'\\downarrow' => '\u2193',
		'\\updownarrow' => '\u2195',
		'\\Uparrow' => '\u21D1',
		'\\Downarrow' => '\u21D3',
		'\\Updownarrow' => '\u21D5',
		'\\backslash' => '\\',
		"\\rangle" => '\u27E9',
		'\\langle' => '\u27E8',
		'\\rbrace' => '}',
		'\\lbrace' => '{',
		// added this attrs additionally for running all tc:
		'\\}' => [ '}', [ "fence" => "false", "stretchy" => "false" ] ],
		// added this attrs additionally for running all tc:
		'\\{' => [ '{', [ "fence" => "false", "stretchy" => "false" ] ],
		'\\rceil' => '\u2309',
		'\\lceil' => '\u2308',
		'\\rfloor' => '\u230B',
		'\\lfloor' => '\u230A',
		'\\lbrack' => '[',
		'\\rbrack' => ']'
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
				$identifierEntry[] = MMLutil::uc2xNotation( $value ); // Just the Unicode character
			} elseif ( is_array( $value ) ) {
				$identifierEntry[] = MMLutil::uc2xNotation( $value[0] ); // Unicode character
				unset( $value[1]['texClass'] ); // Remove the texClass from the array
				if ( !empty( $value[1] ) ) {
					// this converts PHP constants to JSON strings
					$identifierEntry[] = $value[1]; // Additional attributes
				}
			}

			// Check if the entry already exists in texutil.json
			if ( isset( $jsonContent["$key"] ) ) {
				// Preserve existing attributes and only add or update the identifier
				$jsonContent["$key"]['delimiter'] = $identifierEntry;
			} else {
				// Create a new entry if it doesn't exist
				$jsonContent["$key"] = [
					"delimiter" => $identifierEntry
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

		file_put_contents( $jsonFilePath, $jsonStringWithTabs );

		echo "texutil.json successfully updated.\n";
	}
}
$maintClass = UpdateTexutil::class;
require_once RUN_MAINTENANCE_IF_MAIN;
