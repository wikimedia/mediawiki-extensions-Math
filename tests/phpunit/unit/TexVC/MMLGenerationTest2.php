<?php
namespace MediaWiki\Extension\Math\TexVC;

use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLTestUtilHTML;

use MediaWikiUnitTestCase;
use Psr\Log\InvalidArgumentException;

/**
 * This is a very basic test for running more cases for MathML generation.
 * WIP: This tests is for running the specified testfiles in development.
 * Categories can be selected within 'provideTestCases' functions.
 * @covers \MediaWiki\Extension\Math\TexVC\TexVC
 * @group stub
 */
final class MMLGenerationTest2 extends MediaWikiUnitTestCase {
	private static $FILENAME1 = __DIR__ . "/tex-2-mml.json";
	private static $FILENAME2 = __DIR__ . "/ParserTest135.json";
	private static $SELECTEDFILE = 0; // 0 , 1 ... for selecting file
	private static $APPLYFILTER = false;
	private static $FILTERSTART = 0;
	private static $FILTERLENGTH = 1;

	private static $GENERATEHTML = false;
	private static $GENERATEDHTMLFILE = __DIR__ . "/MMLGenerationTest2-Output.html";

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public static function setUpBeforeClass(): void {
		MMLTestUtilHTML::generateHTMLstart( self::$GENERATEDHTMLFILE, [ "name","Tex-Input",
			"MathML(MathJax3)","MathML(TexVC)" ], self::$GENERATEHTML );
	}

	public static function tearDownAfterClass(): void {
		MMLTestUtilHTML::generateHTMLEnd( self::$GENERATEDHTMLFILE, self::$GENERATEHTML );
	}

	/**
	 * @dataProvider provideTestCases
	 */
	public function testTexVC( $title, $tc ) {
		$texVC = new TexVC();

		if ( $tc->skipped ?? false ) {
			MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $tc->ctr, $tc->input,
				"skipped", "skipped" ], false, self::$GENERATEHTML );
			$this->assertTrue( true );
			return;
		}
		# Fetch result from TexVC(PHP)
		$resultT = $texVC->check( $tc->input, [
			'debug' => false,
			'usemathrm' => $tc->usemathrm ?? false,
			'oldtexvc' => $tc->oldtexvc ?? false
		] );
		$mathMLtexVC = MMLTestUtil::getMMLwrapped( $resultT["input"] );
		MMLTestUtilHTML::generateHTMLtableRow( self::$GENERATEDHTMLFILE, [ $tc->ctr, $tc->input, "tbd",
			$mathMLtexVC ], false, self::$GENERATEHTML );
		$this->assertTrue( true );
	}

	public static function provideTestCases() {
		switch ( self::$SELECTEDFILE ) {
			case 0:
				return self::provideTestCases1();
			case 1:
				return self::provideTestCases2();
			default:
				self::throwException( new InvalidArgumentException( "No correct file specified" ) );
				return [];
		}
	}

	/**
	 * Provide testcases and filter and format them for
	 * the first testfile 'tex-2-mml.json'.
	 * @return array
	 */
	public static function provideTestCases1() {
		$res = MMLTestUtil::getJSON( self::$FILENAME1 );
		// TBD refactor the category filter  here
		$f = $res->basic;
		// $f = $res->literalnums;
		// Adding running indices for location of tests.
		$indexCtr = 0;
		foreach ( $f as $tc ) {
			$tc[1]->ctr = $indexCtr;
			$indexCtr += 1;
		}
		// Filtering results by index if necessary
		if ( self::$APPLYFILTER ) {
			$f = array_slice( $f, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $f;
	}

	/**
	 * Provide testcases and filter and format them for
	 * the second testfile 'ParserTest.json'.
	 * @return array
	 */
	public static function provideTestCases2() {
		$res = MMLTestUtil::getJSON( self::$FILENAME2 );
		$f = [];
		// Adding running indices for location of tests.
		$indexCtr = 0;
		foreach ( $res as $tc ) {
			$tc->ctr = $indexCtr;
			$indexCtr += 1;
			array_push( $f, [ "title N/A", $tc ] );
		}
		// Filtering results by index if necessary
		if ( self::$APPLYFILTER ) {
			$f = array_slice( $f, self::$FILTERSTART, self::$FILTERLENGTH );
		}
		return $f;
	}
}
