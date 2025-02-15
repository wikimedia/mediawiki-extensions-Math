<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC;

use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexUtil
 */
class TexUtilTest extends MediaWikiUnitTestCase {

	/**
	 * Basic test for TexUtil
	 */
	public function testTexUtil() {
		TexUtil::removeInstance();
		$tu = TexUtil::getInstance();
		// Testing all functions
		$this->assertTrue( $tu->getAllFunctionsAt( "\\AA" ) );
		$this->assertFalse( $tu->getAllFunctionsAt( "\\notlisted" ) );
		// Testing other functions
		$this->assertTrue( $tu->mhchem_macro_2pc( "\\color" ) );
		$this->assertFalse( $tu->mhchem_macro_2pc( "not listed" ) );
	}

	public function testInvalidCall() {
		TexUtil::removeInstance();
		$tu = TexUtil::getInstance();
		// Testing all functions
		$this->expectException( InvalidArgumentException::class );
		$tu->__call( '\\notlisted', [] );
	}

	public function testUnicodeDefined() {
		$tu = TexUtil::getInstance();
		$sets = [ 'nullary_macro_in_mbox' ];
		foreach ( $sets as $set ) {
			$baseElements = $tu->getBaseElements();
			foreach ( $baseElements[$set] as $key => $value ) {
				$this->assertIsString( $tu->unicode_char( $key ),
					"unicode_char should return a string for $key ($set)" );
			}
		}
	}

	/**
	 * Testing a checksum for the parsed object against a checksum of the json file contents.
	 * @return void
	 */
	public function testChecksum() {
		$tu = TexUtil::getInstance();

		$out = [];
		$sets = [
			'ams_required',
			'big_literals',
			'box_functions',
			'callback',
			'cancel_required',
			'color_function',
			'color_required',
			'declh_function',
			'definecolor_function',
			'delimiter',
			'euro_required',
			'fun_ar1',
			'fun_ar1nb',
			'fun_ar1opt',
			'fun_ar2',
			'fun_ar2nb',
			'fun_ar4',
			'fun_infix',
			'fun_mhchem',
			'hline_function',
			'ignore_identifier',
			'intent_required',
			'latex_function_names',
			'left_function',
			'mathoid_required',
			'mediawiki_function_names',
			'mhchem_bond',
			'mhchem_macro_1p',
			'mhchem_macro_2p',
			'mhchem_macro_2pc',
			'mhchem_macro_2pu',
			'mhchem_required',
			'mhchemtexified_required',
			'mhchem_single_macro',
			'nullary_macro',
			'nullary_macro_in_mbox',
			'operator',
			'other_delimiters1',
			'other_delimiters2',
			'over_operator',
			'right_function',
			'teubner_required',
			'stix_required',
			'unicode_char',
		];

		// Reading data from TexUtil.
		foreach ( $sets as $set ) {
			$baseElements = $tu->getBaseElements();
			foreach ( $baseElements[$set] as $key => $value ) {
				if ( !array_key_exists( $key, $out ) ) {
					$out[$key] = [];
				}
				$out[$key][$set] = $value;
			}
		}

		$maps = [
			'deprecated_nullary_macro_aliase',
			'nullary_macro_aliase',
			'other_delimiters2',
			'other_fun_ar1',
			'is_literal',
			'is_letter_mod',
			'intent_required',
			'identifier'
		];

		foreach ( $maps as $map ) {
			$baseElements = $tu->getBaseElements();
			foreach ( $baseElements[$map] as $key => $value ) {
				if ( !array_key_exists( $key, $out ) ) {
					$out[$key] = [];
				}
				$out[$key][$map] = $value;
			}
		}

		// Sorting output alphabetically encP - out not sorted correctly
		ksort( $out );
		foreach ( $out as &$op ) {
			ksort( $op );
		}

		// Loading local json file
		$file = TexUtil::getJsonFile();
		// json_encode cannot generate tabs required by WMF convention https://github.com/php/php-src/issues/8864
		$encP = json_encode( $out, JSON_PRETTY_PRINT );
		$encP = preg_replace( '/\n\s+/', "\n", $encP ) . "\n";
		// unescape slashes for comparison as escaping is not allowed in the json file
		$encP = str_replace( '\/', '/', $encP );
		$file = preg_replace( '/\n\s+/', "\n", $file );
		$hashOutput = $this->getHash( $encP );
		$hashFile = $this->getHash( $file );
		// uncomment the following lines to spot differences in your IDE
		// $this->assertEquals( $encP, $file );
		$this->assertEquals( $hashFile, $hashOutput );
	}

	private function getHash( $input ) {
		return hash( 'sha256', $input );
	}
}
