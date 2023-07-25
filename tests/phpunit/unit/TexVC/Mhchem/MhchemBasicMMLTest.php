<?php

namespace MediaWiki\Extension\Math\TexVC\Mhchem;

use MediaWiki\Extension\Math\TexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * Some simple tests for testing MML output of TeXVC for
 * equations containing mhchem. Test parsing the new TeX-commands introduced
 * to TexVC for parsing texified mhchem output.
 *
 * @covers \MediaWiki\Extension\Math\TexVC\TexVC
 *
 */
final class MhchemBasicMMLTest extends MediaWikiUnitTestCase {
	public function testMskip() {
		$input = "\\ce{Cr^{+3}(aq)}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$checkRes = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mspace width="0.111em"></mspace>',
			$checkRes["input"]->renderMML() );
	}

	public function testMkern() {
		$input = "\\ce{A, B}";
		$texVC = new TexVC();
		$options = [ "usemhchem" => true, "usemhchemtexified" => true ];
		$warnings = [];
		$checkRes = $texVC->check( $input, $options, $warnings, true );
		$this->assertStringContainsString( '<mspace width="0.333em"></mspace>',
			$checkRes["input"]->renderMML() );
	}

	public function testRaise() {
		$input = "\\raise{.2em}{-}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="+.2em" depth="-.2em" voffset="+.2em">',
			$checkRes["input"]->renderMML() );
	}

	public function testLower() {
		$input = "\\lower{1em}{-}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="-1em" depth="+1em" voffset="-1em">',
			$checkRes["input"]->renderMML() );
	}

	public function testLower2() {
		$input = "\\lower{-1em}{b}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="+1em" depth="-1em" voffset="+1em">',
			$checkRes["input"]->renderMML() );
	}

	public function testLlap() {
		$input = "\\llap{4}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded width="0" lspace="-1width"><mn>4</mn></mpadded>',
			$checkRes["input"]->renderMML() );
	}

	public function testRlap() {
		$input = "\\rlap{-}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded width="0"><mo>&#x2212;</mo></mpadded>',
			$checkRes["input"]->renderMML() );
	}

	public function testSmash1() {
		$input = "\\smash[t]{2}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="0">', $checkRes["input"]->renderMML() );
	}

	public function testSmash2() {
		$input = "\\smash[b]{x}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded depth="0">', $checkRes["input"]->renderMML() );
	}

	public function testSmash3() {
		$input = "\\smash[bt]{2}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">',
			$checkRes["input"]->renderMML() );
	}

	public function testSmash4() {
		$input = "\\smash[tb]{2}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$this->assertStringContainsString( '<mpadded height="0" depth="0">',
			$checkRes["input"]->renderMML() );
	}

	public function testSmash5() {
		$input = "\\smash{2}";
		$texVC = new TexVC();
		$warnings = [];
		$checkRes = $texVC->check( $input, [ "usemhchem" => true, "usemhchemtexified" => true ],
			$warnings, true );
		$ar = $checkRes["input"]->renderMML();
		$this->assertStringContainsString( '<mpadded height="0" depth="0"', $ar );
	}

}
