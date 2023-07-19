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
}
