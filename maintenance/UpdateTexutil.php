<?php

// Contents of MATHCHAR0MI
const MATHCHAR0MI = [
	"alpha" => '\u03B1',
	"beta" => '\u03B2',
	"gamma" => '\u03B3',
	"delta" => '\u03B4',
	"epsilon" => '\u03F5',
	"zeta" => '\u03B6',
	"eta" => '\u03B7',
	"theta" => '\u03B8',
	"iota" => '\u03B9',
	"kappa" => '\u03BA',
	"lambda" => '\u03BB',
	"mu" => '\u03BC',
	"nu" => '\u03BD',
	"xi" => '\u03BE',
	"omicron" => '\u03BF',
	"pi" => '\u03C0',
	"rho" => '\u03C1',
	"sigma" => '\u03C3',
	"tau" => '\u03C4',
	"upsilon" => '\u03C5',
	"phi" => '\u03D5',
	"chi" => '\u03C7',
	"psi" => '\u03C8',
	"omega" => '\u03C9',
	"varepsilon" => '\u03B5',
	"vartheta" => '\u03D1',
	"varpi" => '\u03D6',
	"varrho" => '\u03F1',
	"varsigma" => '\u03C2',
	"varphi" => '\u03C6',
	"S" => [ '\u00A7', [ "mathvariant" => "normal" ] ],
	"aleph" => [ '\u2135', [ "mathvariant" => "normal" ] ],
	"hbar" => [ '\u210F', [ "alternate" => "1" ] ],
	"imath" => '\u0131',
	"jmath" => '\u0237',
	"ell" => '\u2113',
	"wp" => [ '\u2118', [ "mathvariant" => "normal" ] ],
	"Re" => [ '\u211C', [ "mathvariant" => "normal" ] ],
	"Im" => [ '\u2111', [ "mathvariant" => "normal" ] ],
	"partial" => [ '\u2202', [] ],
	"infty" => [ '\u221E', [ "mathvariant" => "normal" ] ],
	"prime" => [ '\u2032', [ "alternate" => "1" ] ],
	"emptyset" => [ '\u2205', [ "mathvariant" => "normal" ] ],
	"nabla" => [ '\u2207', [ "mathvariant" => "normal" ] ],
	"top" => [ '\u22A4', [ "mathvariant" => "normal" ] ],
	"bot" => [ '\u22A5', [ "mathvariant" => "normal" ] ],
	"angle" => [ '\u2220', [ "mathvariant" => "normal" ] ],
	"triangle" => [ '\u25B3', [ "mathvariant" => "normal" ] ],
	"backslash" => [ '\u2216', [ "mathvariant" => "normal" ] ],
	"forall" => [ '\u2200', [ "mathvariant" => "normal" ] ],
	"exists" => [ '\u2203', [ "mathvariant" => "normal" ] ],
	"neg" => [ '\u00AC', [ "mathvariant" => "normal" ] ],
	"lnot" => [ '\u00AC', [ "mathvariant" => "normal" ] ],
	"flat" => [ '\u266D', [ "mathvariant" => "normal" ] ],
	"natural" => [ '\u266E', [ "mathvariant" => "normal" ] ],
	"sharp" => [ '\u266F', [ "mathvariant" => "normal" ] ],
	"clubsuit" => [ '\u2663', [ "mathvariant" => "normal" ] ],
	"diamondsuit" => [ '\u2662', [ "mathvariant" => "normal" ] ],
	"heartsuit" => [ '\u2661', [ "mathvariant" => "normal" ] ],
	"spadesuit" => [ '\u2660', [ "mathvariant" => "normal" ] ]
];

// Path to texutil.json
$jsonFilePath = './src/WikiTexVC/texutil.json';

// Load texutil.json as an associative array
$jsonContent = json_decode( file_get_contents( $jsonFilePath ), true );

if ( $jsonContent === null ) {
	die( "Failed to decode texutil.json. Please check the file format.\n" );
}

// Iterate over MATHCHAR0MI and update the jsonContent
foreach ( MATHCHAR0MI as $key => $value ) {
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

// Convert the JSON content to a string with spaces for pretty-printing
$jsonString = json_encode( $jsonContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

// Replace spaces with tabs for indentation cf. https://github.com/php/php-src/issues/8864
$jsonStringWithTabs = preg_replace_callback( '/^( +)/m', static function ( $matches ) {
	// Convert spaces to tabs (assuming 4 spaces per tab level)
	return str_repeat( "\t", strlen( $matches[1] ) / 4 );
}, $jsonString ) . "\n";

// Save the modified JSON with tab-indented formatting
file_put_contents( $jsonFilePath, $jsonStringWithTabs );

echo "texutil.json successfully updated.\n";
