<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Lengths\MathSpace;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Align;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

class UpdateTexutil extends Maintenance {

	private const LEGACY_CONCEPTS = [
		"mathring" => [ 'accent', '02DA' ],
		"nobreakspace" => 'Tilde',
		"negmedspace" => [ 'spacer', MathSpace::NEGATIVEMEDIUMMATHSPACE ],
		"negthickspace" => [ 'spacer', MathSpace::NEGATIVETHICKMATHSPACE ],
		"idotsint" => [ 'MultiIntegral', '\\int\\cdots\\int' ],
		"dddot" => [ 'accent', '20DB' ],
		"ddddot" => [ 'accent', '20DC' ],
		"sideset" => 'SideSet',
		"boxed" => [ 'macro', '\\fbox{$\\displaystyle{#1}$}', 1 ],
		"tag" => 'HandleTag',
		"notag" => 'HandleNoTag',
		"eqref" => [ 'HandleRef', true ],
		"substack" => [ 'macro', '\\begin{subarray}{c}#1\\end{subarray}', 1 ],
		"injlim" => [ 'namedOp', 'inj lim' ],
		"projlim" => [ 'namedOp', 'proj lim' ],
		"varliminf" => [ 'macro', '\\mathop{\\underline{\\mmlToken{mi}{lim}}}' ],
		"varlimsup" => [ 'macro', '\\mathop{\\overline{\\mmlToken{mi}{lim}}}' ],
		// replaced underrightarrow here not supported
		"varinjlim" => [ 'macro', '\\mathop{\\xrightarrow{\\mmlToken{mi}{lim}}}' ],
		// replaced underleftarrow here not supported
		"varprojlim" => [ 'macro', '\\mathop{\\xleftarrow{\\mmlToken{mi}{lim}}}' ],
		"DeclareMathOperator" => 'HandleDeclareOp',
		"operatorname" => 'handleOperatorName',
		"genfrac" => 'genFrac',
		"frac" => [ 'genFrac', '', '', '', '' ],
		"tfrac" => [ 'genFrac', '', '', '', '1' ],
		"dfrac" => [ 'genFrac', '', '', '', '0' ],
		"binom" => [ 'genFrac', '(', ')', '0', '0' ],
		"tbinom" => [ 'genFrac', '(', ')', '0', '1' ],
		"dbinom" => [ 'genFrac', '(', ')', '0', '0' ],
		"cfrac" => 'cFrac',
		"shoveleft" => [ 'HandleShove', Align::LEFT ],
		"shoveright" => [ 'HandleShove', Align::RIGHT ],
		"xrightarrow" => [ 'xArrow', 0x2192, 5, 10 ],
		"xleftarrow" => [ 'xArrow', 0x2190, 10, 5 ]
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

			// Check if the entry already exists in texutil.json
			if ( isset( $jsonContent["$key"] ) ) {
				// Preserve existing attributes and only add or update the identifier
				$jsonContent["$key"]['macro_rendering'] = $value;
			} else {
				// Create a new entry if it doesn't exist
				$jsonContent["$key"] = [
					"macro_rendering" => $value
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
