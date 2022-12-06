<?php

namespace MediaWiki\Extension\Math\Tests\TexVC;

use InvalidArgumentException;
use MediaWiki\Extension\Math\TexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * Currently WIP functionalities of en-wiki-formulae.js testsuite.
 * All assertions are currently deactivated, cause high memory load on CI.
 * These tests can be run locally by enabling the ACTIVE flag.
 * File download of the json-input can be done by running:
 * $ cd maintenance && ./downloadMoreTexVCtests.sh
 * @covers \MediaWiki\Extension\Math\TexVC\Parser
 * @group Stub
 */
class EnWikiFormulaeTest extends MediaWikiUnitTestCase {
	private $ACTIVE = true; # indicate whether this test is active
	private $FILENAME = "en-wiki-formulae-good.json";
	private $CHUNKSIZE = 1000;

	/**
	 * Reads the json file to an object
	 * @throws InvalidArgumentException File with testcases does not exists.
	 * @return array json with testcases
	 */
	private function getJSON() {
		$filePath = __DIR__ . '/' . $this->FILENAME;
		if ( !file_exists( $filePath ) ) {
			throw new InvalidArgumentException( "No testfile found at specified path: " . $filePath );
		}
		$file = file_get_contents( $filePath );
		$json = json_decode( $file, true );
		return $json;
	}

	private static function mkgroups( $arr, $n ) {
		$result = [];
		$group = [];
		$groupNo = 1;
		foreach ( $arr as $key => $elem ) {
			$group[$key] = $elem;
			if ( count( $group ) >= $n ) {
				$result["Group $groupNo"] = [ $group ];
				$groupNo++;
				$group = [];
			}
		}
		if ( count( $group ) > 0 ) {
			$result["Group $groupNo"] = [ $group ];
		}
		return $result;
	}

	public function provideTestCases(): array {
		return self::mkgroups( $this->getJSON(), $this->CHUNKSIZE );
	}

	/**
	 * @dataProvider provideTestCases
	 */
	public function testRunCases( $testcase ) {
		if ( !$this->ACTIVE ) {
			$this->markTestSkipped( "All MediaWiki formulae en test not active and skipped. This is expected." );
		}

		$texVC = new TexVC();

		foreach ( $testcase as $hash => $tex ) {
			try {
				$result = $texVC->check( $tex, [
					"debug" => false,
					"usemathrm" => false,
					"oldtexvc" => false
				] );

				$good = ( $result["status"] === '+' );
				$this->assertTrue( $good,  $hash . " with input: " . $tex );
				$r1 = $texVC->check( $result["output"] );
				$this->assertEquals( "+", $r1["status"],
					"error rechecking output: " . $tex . " -> " . $result["output"] );
			} catch ( PhpPegJs\SyntaxError $ex ) {
				$message = "Syntax error: " . $ex->getMessage() .
					' at line ' . $ex->grammarLine . ' column ' .
					$ex->grammarColumn . ' offset ' . $ex->grammarOffset;

				$this->assertTrue( false,  $message );
			}
		}
	}
}
