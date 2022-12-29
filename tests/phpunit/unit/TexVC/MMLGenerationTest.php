<?php

namespace MediaWiki\Extension\Math\Tests\TexVC;

use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLTestUtilHTML;
use MediaWiki\Extension\Math\TexVC\TexUtil;
use MediaWiki\Extension\Math\TexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * This test is checking the MathML3 generation from LaTeX by TexVC.
 * It creates a list of basic LaTeX statements from the supported functions
 * of TexVC from TexUtil.php.
 * @covers \MediaWiki\Extension\Math\TexVC\TexVC
 */
class MMLGenerationTest extends MediaWikiUnitTestCase {
	private static $SKIPXMLVALIDATION = true;
	private static $APPLYFILTER = false;
	private static $APPLYCATEGORYFILTER = false;
	private static $FILTEREDCATEGORIES = [ "fun_ar1" ];
	private static $FILTERSTART = 15;
	private static $FILTERLENGTH = 1;

	private static $GENERATEHTML = false;
	private static $GENERATEDHTMLFILE = __DIR__ . "/MMLGenerationTest-Output.html";
	private static $MMLLOOKUPFILE = __DIR__ . "/TexUtilMMLLookup.json";

	/**
	 * @dataProvider provideTestCases
	 */
	public function testTexVC( $title, $input ) {
		$texVC = new TexVC();
		$useMHChem = self::getMHChem( $title );

		// Fetching the result from TexVC
		$resultT = $texVC->check( $input, [
			'debug' => false,
			'usemathrm' => false,
			'oldtexvc' => false,
			'usemhchem' => $useMHChem
		] );

		// Comparing the result either to MathML result from Mathoid/Mathjax or from LaTeXML
		$this->validateWithLookup( $resultT["input"], $input, $title );
	}

	private const SETS = [
		'big_literals',
		'box_functions',
		'color_function',
		'declh_function',
		'definecolor_function',
		'fun_ar1',
		'fun_ar1nb',
		'fun_ar1opt',
		'fun_ar2',
		'fun_ar2nb',
		'fun_infix',
		'fun_mhchem',
		'hline_function',
		'latex_function_names',
		'left_function',
		'mediawiki_function_names',
		'mhchem_bond',
		'mhchem_macro_1p',
		'mhchem_macro_2p',
		'mhchem_macro_2pc',
		'mhchem_macro_2pu',
		'mhchem_single_macro',
		'nullary_macro',
		'nullary_macro_in_mbox',
		'other_delimiters1',
		'other_delimiters2',
		'right_function'
	];

	private const ARG_CNTS = [
		"big_literals" => 1,
		"box_functions" => 1,
		"color_function" => 1,
		"definecolor_function" => 1,
		"fun_ar1" => 1,
		"fun_ar1nb" => 1,
		"fun_ar1opt" => 1,
		"fun_ar2" => 2,
		"fun_infix" => 1,
		"fun_ar2nb" => 5,
		"fun_mhchem" => 1,
		"left_function" => 1,
		"right_function" => 1,
		"mhchem_bond" => 1,
		"mhchem_macro_1p" => 1,
		"mhchem_macro_2p" => 2,
		"mhchem_macro_2pu" => 1
	];
	private const OTHER_ARGS = [
		"declh_function" => true,
	];

	private const SAMPLE_ARGS_RIGHT = [
		"big_literals" => '(',
		"color_function" => '{red}{red}',
		"mhchem_macro_2pc" => '{red}{red}',
		"definecolor_function" => '{ultramarine}{RGB}{0,32,96}',
		"fun_ar2nb" => '{_1^2}{_3^4}\\sum',
		"left_function" => '( \\right.',
		"mhchem_bond" => '{-}',
		"right_function" => ')',

	];

	private const SAMPLE_ARGS_LEFT = [
		"right_function" => '\\left(',
	];

	private const ENTRY_ARGS = [
		"\\atop" => "{ a \\atop b }",
		"\\choose" => "{ a \\choose b }",
		"\\over" => "{a \\over b }",
		"\\hline" => "\n\\begin{array}{|c||c|} a & b  \\\\\n\\hline\n1&2 \n\\end{array}\n",
		"\\nolimits" => " \mathop{\\rm cos}\\nolimits^2",
	   // "\\limits" =>" \mathop{\\rm cos}\\limits^2",
		"\\limits" => "\\lim\\limits_{x \\to 2}",
		"\\displaystyle"  => "\\frac{\\displaystyle \\sum_{k=1}^N k^2}{a}",
		"\\scriptscriptstyle" => "\\frac ab + \\scriptscriptstyle{\\frac cd + \\frac ef} + \\frac gh",
		"\\scriptstyle" => "{\\scriptstyle \\partial \\Omega}",
		"\\textstyle" => "\\textstyle \\sum_{k=1}^N k^2",
		// Failing examples: ="\\vbox{{a}{b}}""\\vbox{\\vhb{eight}\\vhb{gnat}}"
		// "\\vbox{\\hbox{eight}\\hbox{gnat}}";
		"\\vbox" => "\\vbox{ab}",
		"\\emph" => "\\mathit{\\emph{a}} \\emph{b}",
		// it seems not supported for math, not in any other en_wiki test etc. probably make sense
		// to drop or substitute with \\vert
		"\\vline" => "\n\\begin{array}{|c||c|} a & b \\vline c  \\\\
		\\hline\n1&2 \n\\end{array}\n",
	];

	/**
	 * Check from the test title if it is a mhchem-test.
	 * Return a boolean indicator for this.
	 * @param string $title test title
	 * @return bool indicator if the test is mhchem related
	 */
	public static function getMHChem( string $title ): bool {
		$useMHChem = false;
		if ( str_contains( $title, "chem" ) ) {
			$useMHChem = true;
		}
		return $useMHChem;
	}

	public function validateWithLookup( $resTexVC, $input, $title ): void {
		$mathMLtexVC = MMLTestUtil::getMMLwrapped( $resTexVC );
		$mmlLatexML = (array)MMLTestUtil::getJSON( self::$MMLLOOKUPFILE );
		$resMML3latexml = $mmlLatexML[$input] ?? "merror";
		if ( str_contains( $resMML3latexml, "merror" ) ) {
			$errorMessage = "Error Rendering in MathJax";
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, $title, $input, $errorMessage,
													$mathMLtexVC, false, self::$GENERATEHTML );
			$this->assertTrue( true );
		} else {
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, $title, $input, $resMML3latexml,
													$mathMLtexVC, false, self::$GENERATEHTML );
			$resMML3latexml = $resMML3latexml ?: "<math> no MML3 from Lookup </math>";
			if ( !self::$SKIPXMLVALIDATION ) {
				$this->assertXmlStringEqualsXmlString( $resMML3latexml, $mathMLtexVC );
			} else {
				$this->assertTrue( true );
			}
		}
	}

	public static function setUpBeforeClass(): void {
		MMLTestUtilHTML::generateHTMLstart( self::$GENERATEDHTMLFILE, "MathML(MathJax3)", self::$GENERATEHTML );
	}

	public static function tearDownAfterClass(): void {
		MMLTestUtilHTML::generateHTMLEnd( self::$GENERATEDHTMLFILE, self::$GENERATEHTML );
	}

	/**
	 * Generate testcases with texutil, filter them and provide them to the testrunner.
	 * @return array
	 */
	public static function provideTestCases() {
		$groups = self::createGroups();
		$overAllCtr = 0;
		$finalCases = [];
		foreach ( $groups  as $category => $group ) {
			if ( self::$APPLYCATEGORYFILTER && !in_array( $category, self::$FILTEREDCATEGORIES ) ) {
				continue;
			}
			$indexCtr = 0;
			foreach ( $group as $case ) {
				$title = "set#" . $overAllCtr . ": " . $category . $indexCtr;
				$finalCases[$title] = [ $title, $case ];
				$indexCtr++;
				$overAllCtr++;
			}
		}
		if ( self::$APPLYFILTER ) {
			$finalCases = array_slice( $finalCases, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $finalCases;
	}

	private static function addArgs( $set, $entry ) {
		if ( isset( self::ENTRY_ARGS[$entry] ) ) {
			// Some entries have specific mappings for non-group related arguments
			return ( self::ENTRY_ARGS[$entry] );
		}
		$count = !isset( self::ARG_CNTS[$set] ) ? 0 : self::ARG_CNTS[$set];
		$argsR = '';
		$argsL = '';
		if ( !isset( self::SAMPLE_ARGS_RIGHT[$set] ) ) {
			for ( $i = 0; $i < $count; $i++ ) {
				$argsR .= '{' . chr( 97 + $i ) . '}';
			}
		} else {
			$argsR = self::SAMPLE_ARGS_RIGHT[$set];
		}
		if ( isset( self::SAMPLE_ARGS_LEFT[$set] ) ) {
			$argsL = self::SAMPLE_ARGS_LEFT[$set];
		}
		if ( $argsR == '' && isset( self::OTHER_ARGS[$set] ) ) {
			if ( self::OTHER_ARGS[$set] ) {
				return "{" . $entry . " a }";
			}
		}
		if ( str_starts_with( $set, "mhchem" ) ) {
			$rendering = '\\ce{' . $argsL . $entry . $argsR . '}';
		} else {
			$rendering = $argsL . $entry . $argsR;
		}
		return $rendering;
	}

	private static function createGroups() {
		$groups = [];
		foreach ( self::SETS as $set ) {
			$entries = array_keys( TexUtil::getInstance()->getBaseElements()[$set] );
			foreach ( $entries as &$entry ) {
				$entry = self::addArgs( $set, $entry );
			}
			$groups[$set] = $entries;
		}
		return $groups;
	}
}
