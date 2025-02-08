<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

class UpdateTexutil extends Maintenance {

	private const LEGACY_CONCEPTS = [
		"surd" => '\u221A',
		"coprod" => [ '\u2210', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigvee" => [ '\u22C1', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigwedge" => [ '\u22C0', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"biguplus" => [ '\u2A04', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigcap" => [ '\u22C2', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigcup" => [ '\u22C3', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"int" => [ '\u222B', [ "texClass" => TexClass::OP ] ],
		"intbar" => [ '\u2A0D', [ "texClass" => TexClass::OP ] ],
		"intBar" => [ '\u2A0E', [ "texClass" => TexClass::OP ] ],
		"intop" => [ '\u222B', [ "texClass" => TexClass::OP,
			"movesupsub" => true, "movablelimits" => true ] ],
		"iint" => [ '\u222C', [ "texClass" => TexClass::OP ] ],
		"iiint" => [ '\u222D', [ "texClass" => TexClass::OP ] ],
		"prod" => [ '\u220F', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"sum" => [ '\u2211', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigotimes" => [ '\u2A02', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigoplus" => [ '\u2A01', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigodot" => [ '\u2A00', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"bigsqcup" => [ '\u2A06', [ "texClass" => TexClass::OP,
			"movesupsub" => true ] ],
		"smallint" => [ '\u222B', [ "largeop" => false ] ],
		"triangleleft" => '\u25C3',
		"triangleright" => '\u25B9',
		"bigtriangleup" => '\u25B3',
		"bigtriangledown" => '\u25BD',
		"wedge" => '\u2227',
		"land" => '\u2227',
		"vee" => '\u2228',
		"lor" => '\u2228',
		"cap" => '\u2229',
		"cup" => '\u222A',
		"ddagger" => '\u2021',
		"dagger" => '\u2020',
		"sqcap" => '\u2293',
		"sqcup" => '\u2294',
		"uplus" => '\u228E',
		"amalg" => '\u2A3F',
		"diamond" => '\u22C4',
		"bullet" => '\u2219',
		"wr" => '\u2240',
		"div" => '\u00F7',
		"divsymbol" => '\u00F7',
		"odot" => [ '\u2299', [ "largeop" => false ] ],
		"oslash" => [ '\u2298', [ "largeop" => false ] ],
		"otimes" => [ '\u2297', [ "largeop" => false ] ],
		"ominus" => [ '\u2296', [ "largeop" => false ] ],
		"oplus" => [ '\u2295', [ "largeop" => false ] ],
		"mp" => '\u2213',
		"pm" => '\u00B1',
		"circ" => '\u2218',
		"bigcirc" => '\u25EF',
		"setminus" => '\u2216',
		"cdot" => '\u22C5',
		"ast" => '\u2217',
		"times" => '\u00D7',
		"star" => '\u22C6',
		"propto" => '\u221D',
		"sqsubseteq" => '\u2291',
		"sqsupseteq" => '\u2292',
		"parallel" => '\u2225',
		"mid" => '\u2223',
		"dashv" => '\u22A3',
		"vdash" => '\u22A2',
		"leq" => '\u2264',
		"le" => '\u2264',
		"geq" => '\u2265',
		"ge" => '\u2265',
		"lt" => '\u003C',
		"gt" => '\u003E',
		"succ" => '\u227B',
		"prec" => '\u227A',
		"approx" => '\u2248',
		"succeq" => '\u2AB0',
		"preceq" => '\u2AAF',
		"supset" => '\u2283',
		"subset" => '\u2282',
		"supseteq" => '\u2287',
		"subseteq" => '\u2286',
		"in" => '\u2208',
		"ni" => '\u220B',
		"notin" => '\u2209',
		"owns" => '\u220B',
		"gg" => '\u226B',
		"ll" => '\u226A',
		"sim" => '\u223C',
		"simeq" => '\u2243',
		"perp" => '\u22A5',
		"equiv" => '\u2261',
		"asymp" => '\u224D',
		"smile" => '\u2323',
		"frown" => '\u2322',
		"ne" => '\u2260',
		"neq" => '\u2260',
		"cong" => '\u2245',
		"doteq" => '\u2250',
		"bowtie" => '\u22C8',
		"models" => '\u22A8',
		"notChar" => '\u29F8',
		"Leftrightarrow" => '\u21D4',
		"Leftarrow" => '\u21D0',
		"Rightarrow" => '\u21D2',
		"leftrightarrow" => '\u2194',
		"leftarrow" => '\u2190',
		"gets" => '\u2190',
		"rightarrow" => '\u2192',
		"to" => [ '\u2192', [ "accent" => "false" ] ],
		"mapsto" => [ '\u21A6', [ "stretchy" => "false" ] ], // added stretchy for tests
		"leftharpoonup" => '\u21BC',
		"leftharpoondown" => '\u21BD',
		"rightharpoonup" => '\u21C0',
		"rightharpoondown" => '\u21C1',
		"nearrow" => '\u2197',
		"searrow" => '\u2198',
		"nwarrow" => '\u2196',
		"swarrow" => '\u2199',
		"rightleftharpoons" => '\u21CC',
		"hookrightarrow" => '\u21AA',
		"hookleftarrow" => '\u21A9',
		"longleftarrow" => '\u27F5',
		"Longleftarrow" => '\u27F8',
		"longrightarrow" => '\u27F6',
		"Longrightarrow" => '\u27F9',
		"Longleftrightarrow" => '\u27FA',
		"longleftrightarrow" => '\u27F7',
		"longmapsto" => [ '\u27FC', [ "stretchy" => "false" ] ], // added stretchy for test
		"ldots" => '\u2026',
		"cdots" => '\u22EF',
		// "cdots" => '\u2026', // fallback
		"vdots" => '\u22EE',
		"ddots" => '\u22F1',
		"dotsc" => '\u2026',
		"dotsb" => '\u22EF',
		// "dotsb" => '\u2026', // fallback
		"dotsm" => '\u22EF',
		// "dotsm" => '\u2026', // fallback
		"dotsi" => '\u22EF',
		// "dotsi" => '\u2026', // fallback

		"dotso" => '\u2026',
		"ldotp" => [ '\u002E', [ "texClass" => TexClass::PUNCT ] ],
		"cdotp" => [ '\u22C5', [ "texClass" => TexClass::PUNCT ] ],
		"colon" => [ '\u003A', [ "texClass" => TexClass::PUNCT ] ]
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
			if ( is_string( $value ) ) {
				$value = [ $value ];
			}
			$value[0] = MMLutil::uc2xNotation( $value[0] );
			// Remove the texClass from the array
			unset( $value[1]['texClass'] );

			// Check if the entry already exists in texutil.json
			if ( isset( $jsonContent["$key"] ) ) {
				// Preserve existing attributes and only add or update the identifier
				$jsonContent["$key"]['operator_rendering'] = $value;
			} else {
				// Create a new entry if it doesn't exist
				$jsonContent["$key"] = [
					"operator_rendering" => $value
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
