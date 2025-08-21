<?php

namespace MediaWiki\Extension\Math\WikiTexVC\Mhchem;

use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiIntegrationTestCase;

/**
 * Some simple tests for testing MML output of TeXVC for
 * equations containing mhchem. Test parsing the new TeX-commands introduced
 * to WikiTexVC for parsing texified mhchem output.
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\TexVC
 *
 */
final class MhchemBasicMMLTest extends MediaWikiIntegrationTestCase {

	public function testGUIStyleNotation() {
		$input = "{\displaystyle \ce{ C6H5-CHO }}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->toMMLtree();
		$this->assertStringContainsString( '<mpadded', $mml );
		$this->assertStringContainsString( '<mphantom', $mml );
	}

	public static function provideTestCasesLetters() {
		return [
			[ "Alpha", "A" ],
			[ "Beta", "B" ],
			[ "Chi", "X" ],
			[ "Epsilon", "E" ],
			[ "Eta", "H" ],
			[ "Iota", "I" ],
			[ "Kappa", "K" ],
			[ "Mu", "M" ],
			[ "Nu", "N" ],
			[ "Omicron", "O" ],
			[ "Rho", "P" ],
			[ "Tau", "T" ],
			[ "Zeta", "Z" ]
		];
	}

	/**
	 * @dataProvider provideTestCasesLetters
	 */
	public function testmhchemLetters( $case, $result ) {
		$input = "\ce{\\" . $case . " \ca }";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->toMMLtree();
		$this->assertStringContainsString( '<mi', $mml );
		$this->assertStringContainsString( $result . '</mi>', $mml );
		$this->assertStringContainsString( '&#x223C;</mo>', $mml );
	}

	public function testHarpoonsLeftRight() {
		$input = "A \\longLeftrightharpoons L";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->toMMLtree();
		$this->assertStringContainsString( '<mpadded height="0" depth="0">', $mml );
		$this->assertStringContainsString( '<mspace ', $mml );
	}

	public function testHarpoonsRightLeft() {
		$input = "A \\longRightleftharpoons R";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->toMMLtree();
		$this->assertStringContainsString( '&#x2212;</mo>', $mml );
		$this->assertStringContainsString( '&#x21C0;', $mml );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">', $mml );
		$this->assertStringContainsString( '<mspace ', $mml );
	}

	public function testArrowsLeftRight() {
		$input = "A \\longleftrightarrows C";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$mml = $res['input']->toMMLtree();
		$this->assertStringContainsString( '<mo stretchy="false">&#x27F5;</mo>', $mml );
		$this->assertStringContainsString( '<mo stretchy="false">&#x27F6;</mo>', $mml );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">', $mml );
		$this->assertStringContainsString( '<mspace ', $mml );
	}

	public static function provideTexVCCheckData() {
		return [
			[
				"\\tripledash \\frac{a}{b}",
				'<mo>&#x2014;</mo>'
			],
			[
				"\\displaystyle{\\mathchoice{a}{b}{c}{d}}",
				'<mstyle displaystyle="true" scriptlevel="0"><mi>a</mi></mstyle>'
			],
			[
				"\\textstyle{\\mathchoice{a}{b}{c}{d}}",
				'<mstyle displaystyle="false" scriptlevel="0"><mi>b</mi></mstyle>'
			],
			[
				"\\scriptstyle{\\mathchoice{a}{b}{c}{d}}",
				'<mstyle displaystyle="false" scriptlevel="1"><mi>c</mi></mstyle>'
			],
			[
				"\\scriptscriptstyle{\\mathchoice{a}{b}{c}{d}}",
				'<mstyle displaystyle="false" scriptlevel="2"><mi>d</mi></mstyle>'
			],
			[
				"\\ce{Cr^{+3}(aq)}",
				'<mspace width="0.111em"></mspace>'
			],
			[
				"\\ce{A, B}",
				'<mspace width="0.333em"></mspace>'
			],
			[
				"\\raise{.2em}{-}",
				'<mpadded height="+.2em" depth="-.2em" voffset="+.2em">'
			],
			[
				"\\lower{1em}{-}",
				'<mpadded height="-1em" depth="+1em" voffset="-1em">'
			],
			[
				"\\lower{-1em}{b}",
				'<mpadded height="+1em" depth="-1em" voffset="+1em">'
			],
			[
				"\\llap{4}",
				'<mpadded width="0" lspace="-1width"><mn>4</mn></mpadded>'
			],
			[
				"\\rlap{-}",
				'&#x2212;</mo></mpadded>'
			],
			[
				"\ce{\\smash[t]{2}}",
				'<mpadded height="0">'
			],
			[
				"\ce{\\smash[b]{x}}",
				'<mpadded depth="0">'
			],
			[
				"\ce{\\smash[bt]{2}}",
				'<mpadded height="0" depth="0">'
			],
			[
				"\ce{\\smash[tb]{2}}",
				'<mpadded height="0" depth="0">'
			],
			[
				"\ce{\\smash{2}}",
				'<mpadded height="0" depth="0"'
			],
		];
	}

	/** @dataProvider provideTexVCCheckData */
	public function testTexVCCheck( string $input, string $output ) {
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( $output, $res['input']->toMMLtree() );
	}
}
