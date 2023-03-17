<?php

namespace MediaWiki\Extension\Math\Tests\TexVC;

use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLTestUtil;
use MediaWiki\Extension\Math\TexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * These are some specific testcases by MML-Rendering by TexVC.
 * They are explicitly described here instead of in JSON files because
 * Mathoid or LaTeXML do not generate suitable results for reference.
 * @covers \MediaWiki\Extension\Math\TexVC\TexVC
 */
class MMLRenderTest extends MediaWikiUnitTestCase {
	public function testSidesetError() {
		$input = "\\sideset{_1^2}{_3^4}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertTrue( str_contains( $mathMLtexVC, "merror" ) );
	}

	public function testSidesetSum() {
		$input = "\\sideset{_1^2}{_3^4}\\sum";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "merror", $mathMLtexVC );
		$this->assertStringContainsString( "mmultiscripts", $mathMLtexVC );
		$this->assertStringContainsString( "<mprescripts/>", $mathMLtexVC );
		$this->assertStringContainsString( ">&#x2211;", $mathMLtexVC );
	}

	public function testSidesetProd() {
		$input = "\\sideset{_1^2}{_3^4}\\prod";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "merror", $mathMLtexVC );
		$this->assertStringContainsString( "mmultiscripts", $mathMLtexVC );
		$this->assertStringContainsString( "<mprescripts/>", $mathMLtexVC );
		$this->assertStringContainsString( ">&#x220F;", $mathMLtexVC );
	}

	public function testSidesetFQ() {
		$input = "\\sideset{_1^2}{_3^4}\prod_a^b";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringNotContainsString( "merror", $mathMLtexVC );
		$this->assertStringContainsString( "mmultiscripts", $mathMLtexVC );
		$this->assertStringContainsString( "<mprescripts/>", $mathMLtexVC );
		$this->assertStringContainsString( ">&#x220F;", $mathMLtexVC );
		$this->assertStringContainsString( "<mi>a</mi>", $mathMLtexVC );
		$this->assertStringContainsString( "<mi>b</mi>", $mathMLtexVC );
		$this->assertStringContainsString( "movablelimits", $mathMLtexVC );
	}

	public function testLimitsProd() {
		$input = "\\prod\\limits_{j=1}^k A_{\\alpha_j}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "munderover", $mathMLtexVC );
		$this->assertStringContainsString( "&#x220F;", $mathMLtexVC, );
		$this->assertStringContainsString( "msub", $mathMLtexVC, );
	}

	public function testLimitsSum() {
		$input = "\\sum\\limits_{j=1}^k A_{\\alpha_j}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "munderover", $mathMLtexVC );
		$this->assertStringContainsString( "&#x2211;", $mathMLtexVC, );
		$this->assertStringContainsString( "msub", $mathMLtexVC, );
	}

	public function testLimitsLim() {
		$input = "\\lim_{x \\to 2}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "lim", $mathMLtexVC );
		$this->assertStringContainsString( "munder", $mathMLtexVC, );
	}

	public function testRenderSpaceSemicolon() {
		$input = "{\\;}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "mstyle", $mathMLtexVC );
		$this->assertStringContainsString( "mspace", $mathMLtexVC, );
	}

	public function testSpacesAndCommas() {
		$input = "{a}{b , c}\\,";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>,", $mathMLtexVC );
		$this->assertStringContainsString( "mspace", $mathMLtexVC, );
	}

	public function testPilcrowAndSectionSign() {
		$input = "\\P P \\S S";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mi>P", $mathMLtexVC );
		$this->assertStringContainsString( "<mi>S", $mathMLtexVC, );
		$this->assertStringContainsString( "&#xB6;", $mathMLtexVC, );
		$this->assertStringContainsString( "&#xA7;", $mathMLtexVC, );
	}

	public function testDerivatives1() {
		$input = "b_{f''}";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2033;", $mathMLtexVC );
		$this->assertStringContainsString( "msup", $mathMLtexVC, );
	}

	public function testDerivatives2() {
		$input = "f''''(x)";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "<mo>&#x2057;", $mathMLtexVC );
		$this->assertStringContainsString( "msup", $mathMLtexVC, );
	}

	public function testLimitsTextstyle() {
		$input = "\\textstyle \\lim_{n \\to \\infty}x_n";
		$mathMLtexVC = $this->generateMML( $input );
		$this->assertStringContainsString( "lim", $mathMLtexVC );
		$this->assertStringContainsString( "munder", $mathMLtexVC, );
		$this->assertStringContainsString( "movablelimits=\"true\"", $mathMLtexVC );
	}

	private function generateMML( $input, $chem = false ) {
		$texVC = new TexVC();
		$resultT = $texVC->check( $input, [
			'debug' => false,
			'usemathrm' => false,
			'oldtexvc' => false,
			'usemhchem' => $chem
		] );

		return MMLTestUtil::getMMLwrapped( $resultT["input"] ) ?? "<math> error texvc </math>";
	}
}
