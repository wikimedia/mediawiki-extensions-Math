<?php
namespace MediaWiki\Extension\Math\TexVC\Mhchem;

use Exception;
use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLTestUtilHTML;
use MediaWiki\Extension\Math\TexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * This test checks the functionality MHCHem module within MediaWiki environment
 * It is running all defined testcases from "Mhchemv4mml.json" these are from test.html from javascript mhchemparser.
 * Mhchemv4mml.json can be recreated by running the maintenance script JsonToMathML with Mhchemv4tex.json
 * within a MediaWiki environment:
 * 'php extensions/Math/maintenance/JsonToMathML.php
 *   /var/www/html/extensions/Math/tests/phpunit/unit/TexVC/Mhchem/Mhchemv4tex.json
 *   /var/www/html/extensions/Math/tests/phpunit/unit/TexVC/Mhchem/Mhchemv4mml.json -i 3'
 *
 * Settings for running the testbench are defined as constants within this class.
 *
 * @covers \MediaWiki\Extension\Math\TexVC\TexVC
 */
final class MMLmhchemTest extends MediaWikiUnitTestCase {
	private static bool $LOGMHCHEM = false;
	private static bool $SKIPXMLVALIDATION = false;
	private static string $FILENAMEREF = __DIR__ . "/Mhchemv4mml.json";

	private static bool $APPLYFILTER = false;
	private static int $FILTERSTART = 92;
	private static int $FILTERLENGTH = 1;
	private static bool $GENERATEHTML = false;
	private static string $GENERATEDHTMLFILE = __DIR__ . "/MMLmhchemTest-Output.html";
	private static array $SKIPPEDINDICES = [ 36,40,50,51,91,92,93,94 ];

	public static function setUpBeforeClass(): void {
		MMLTestUtilHTML::generateHTMLstart( self::$GENERATEDHTMLFILE, [ "name","TeX-Input",
			"Tex-MhchemParser","Tex-PHP-Mhchem", "MathML-LaTeXML", "MathML-TexVC" ],
			self::$GENERATEHTML );
	}

	public static function tearDownAfterClass(): void {
		MMLTestUtilHTML::generateHTMLEnd( self::$GENERATEDHTMLFILE, self::$GENERATEHTML );
	}

	/**
	 * @dataProvider provideTestCases
	 * @throws Exception
	 */
	public function testTexVC( $title, $tc ) {
		$texVC = new TexVC();

		if ( in_array( $tc->ctr, self::$SKIPPEDINDICES ) ) {
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $tc->ctr, $tc->tex,
				"skipped", "skipped", "skipped" ], false, self::$GENERATEHTML );
			$this->assertTrue( true );
			return;
		}

		# Fetch result from TexVC(PHP)
		$mhchemParser = new MhchemParser( self::$LOGMHCHEM );
		$mhchemOutput = $mhchemParser->toTex( $tc->tex, $tc->typeC );

		$warnings = [];
		$resTexVC = $texVC->check( $mhchemOutput, [
			'debug' => false,
			'usemathrm' => true,
			'oldtexvc' => false,
			'usemhchem' => true,
			"usemhchemtexified" => true
		], $warnings, false );

		$mathMLtexVC = isset( $resTexVC["input"] ) ? MMLTestUtil::getMMLwrapped( $resTexVC["input"] ) :
			"<math> error texvc </math>";

		MMLTestUtilHTML::generateHTMLtableRow(
			self::$GENERATEDHTMLFILE, [ $title,  $tc->tex, $tc->texNew, $mhchemOutput, $tc->mml_latexml ?? "no mathml",
			$mathMLtexVC ],
			false, self::$GENERATEHTML );

		if ( !self::$SKIPXMLVALIDATION ) {
			$this->assertEquals( $tc->texNew, $mhchemOutput );
		} else {
			$this->assertTrue( true );
		}
	}

	public static function provideTestCases() {
		$fileTestcases = MMLTestUtil::getJSON( self::$FILENAMEREF );

		$f = [];
		// Adding running indices for location of tests.
		$ctr = 0;
		foreach ( $fileTestcases as $tcF ) {
			$tc = [
				"ctr" => $ctr,
				"tex" => $tcF->tex,
				"texNew" => $tcF->texNew,
				"type" => $tcF->type,
				"typeC" => $tcF->typeC,
				"mml_mathoid" => $tcF->mmlMathoid,
				"mml_latexml" => $tcF->mmlLaTeXML,
			];
			$f[] = [ "tc#" . str_pad( $ctr, 3, '0', STR_PAD_LEFT ) . " " . $tcF->description,
				(object)$tc ];
			$ctr++;
		}
		// Filtering results by index if necessary
		if ( self::$APPLYFILTER ) {
			$f = array_slice( $f, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $f;
	}
}
