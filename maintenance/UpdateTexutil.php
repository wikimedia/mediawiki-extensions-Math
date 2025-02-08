<?php

require_once __DIR__ . '/../../../maintenance/Maintenance.php';

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;

class UpdateTexutil extends Maintenance {

	private const LEGACY_CONCEPTS = [
		'equation*' => [ 'Equation', null, false ],
		'eqnarray*' => [ 'EqnArray', null, false, true, 'rcl', null, '.5em' ],
		'align' => [ 'EqnArray', null, true, true, 'rl', 'ParseUtil_js_1.default.cols(0, 2)' ],
		'align*' => [ 'EqnArray', null, false, true, 'rl', "ParseUtil_js_1.default.cols(0, 2)" ],
		"multline" => [ 'Multline', null, true ],
		'multline*' => [ 'Multline', null, false ],
		"split" => [ 'EqnArray', null, false, false, 'rl', "ParseUtil_js_1.default.cols(0)" ],
		"gather" => [ 'EqnArray', null, true, true, 'c' ],
		'gather*' => [ 'EqnArray', null, false, true, 'c' ],
		"alignat" => [ 'alignAt', null, true, true ],
		'alignat*' => [ 'alignAt', null, false, true ],
		"alignedat" => [ 'alignAt', null, false, false ],
		"aligned" => [ 'amsEqnArray', null, null, null, 'rl', "ParseUtil_js_1.default.cols(0, 2)", '.5em', 'D' ],
		"gathered" => [ 'amsEqnArray', null, null, null, 'c', null, '.5em', 'D' ],
		"xalignat" => [ 'XalignAt', null, true, true ],
		'xalignat*' => [ 'XalignAt', null, false, true ],
		"xxalignat" => [ 'XalignAt', null, false, false ],
		"flalign" => [ 'FlalignArray', null, true, false, true, 'rlc', 'auto auto fit' ],
		'flalign*' => [ 'FlalignArray', null, false, false, true, 'rlc', 'auto auto fit' ],
		"subarray" => [ 'array', null, null, null, null, "ParseUtil_js_1.default.cols(0)", '0.1em', 'S', 1 ],
		"smallmatrix" => [ 'array', null, null, null, 'c', "ParseUtil_js_1.default.cols(1 / 3)",
			'.2em', 'S', 1 ],
		"matrix" => [ 'array', null, null, null, 'c' ],
		"pmatrix" => [ 'array', null, '(', ')', 'c' ],
		"bmatrix" => [ 'array', null, '[', ']', 'c' ],
		"Bmatrix" => [ 'array', null, '\\{', '\\}', 'c' ],
		"vmatrix" => [ 'array', null, '\\vert', '\\vert', 'c' ],
		"Vmatrix" => [ 'array', null, '\\Vert', '\\Vert', 'c' ],
		'cases' => [ 'matrix', '{', '', 'left left', null, '.1em', null, true ],
		'array' => [ 'matrix' ]
	];

	public function execute() {
		$jsonFilePath = './src/WikiTexVC/texutil.json';

		$jsonContent = json_decode( file_get_contents( $jsonFilePath ), true );

		if ( $jsonContent === null ) {
			die( "Failed to decode texutil.json. Please check the file format.\n" );
		}

		foreach ( self::LEGACY_CONCEPTS as $key => $value ) {
			// $key = '\\' . $key;

			// Check how to handle the value type (string or array)
			// as we don't have strings in the current value of LEGACY_CONCEPTS,
			// phab complains, that we don't need this check. Thus, I commented it out.
			// if ( is_string( $value ) ) {
			//	$value = [ $value ];
			//}
			$value[0] = MMLutil::uc2xNotation( $value[0] );

			// Check if the entry already exists in texutil.json
			if ( isset( $jsonContent["$key"] ) ) {
				// Preserve existing attributes and only add or update the identifier
				$jsonContent["$key"]['environment_rendering'] = $value;
			} else {
				// Create a new entry if it doesn't exist
				$jsonContent["$key"] = [
					"environment_rendering" => $value
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
