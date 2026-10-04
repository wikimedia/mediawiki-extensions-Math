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
 */
final class MhchemBasicMMLTest extends MediaWikiIntegrationTestCase {

	public static function provideTestCasesLetters(): array {
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

	public static function provideTexVCCheckData(): array {
		$letters = [];
		foreach ( self::provideTestCasesLetters() as $value ) {
			$letters[] = [
				"\ce{\\" . $value[0] . " \ca }",
				[
					'<mi',
					$value[1] . '</mi>',
					'∼</mo>'
				]
			];
		}

		return array_merge( $letters, [
			[
				"{\displaystyle \ce{ C6H5-CHO }}",
				[
					'<mpadded',
					'<mphantom'
				]
			],
			[
				"A \\longLeftrightharpoons L",
				[
					'<mpadded height="0" depth="0">',
					'<mspace '
				]
			],
			[
				"A \\longRightleftharpoons R",
				[
					'−</mo>',
					'⇀</mo>',
					'<mpadded height="0" depth="0">',
					'<mspace ',
				]
			],
			[
				"A \\longleftrightarrows C",
				[
					'<mo stretchy="false">&#x27F5;</mo>',
					'<mo stretchy="false">&#x27F6;</mo>',
					'<mpadded height="0" depth="0">',
					'<mspace '
				]
			],
			[
				"\\tripledash \\frac{a}{b}",
				[ '<mo>&#x2014;</mo>' ]
			],
			[
				"\\displaystyle{\\mathchoice{a}{b}{c}{d}}",
				[ '<mstyle displaystyle="true" scriptlevel="0"><mi>a</mi></mstyle>' ]
			],
			[
				"\\textstyle{\\mathchoice{a}{b}{c}{d}}",
				[ '<mstyle displaystyle="false" scriptlevel="0"><mi>b</mi></mstyle>' ]
			],
			[
				"\\scriptstyle{\\mathchoice{a}{b}{c}{d}}",
				[ '<mstyle displaystyle="false" scriptlevel="1"><mi>c</mi></mstyle>' ]
			],
			[
				"\\scriptscriptstyle{\\mathchoice{a}{b}{c}{d}}",
				[ '<mstyle displaystyle="false" scriptlevel="2"><mi>d</mi></mstyle>' ]
			],
			[
				"\\ce{Cr^{+3}(aq)}",
				[ '<mspace width="0.111em"></mspace>' ]
			],
			[
				"\\ce{A, B}",
				[ '<mspace width="0.333em"></mspace>' ]
			],
			[
				"\\raise{.2em}{-}",
				[ '<mpadded height="+.2em" depth="-.2em" voffset="+.2em">' ]
			],
			[
				"\\lower{1em}{-}",
				[ '<mpadded height="-1em" depth="+1em" voffset="-1em">' ]
			],
			[
				"\\lower{-1em}{b}",
				[ '<mpadded height="+1em" depth="-1em" voffset="+1em">' ]
			],
			[
				"\\llap{4}",
				[ '<mpadded width="0" lspace="-1width"><mn>4</mn></mpadded>' ]
			],
			[
				"\\rlap{-}",
				[ '−</mo></mpadded>' ]
			],
			[
				"\ce{\\smash[t]{2}}",
				[ '<mpadded height="0">' ]
			],
			[
				"\ce{\\smash[b]{x}}",
				[ '<mpadded depth="0">' ]
			],
			[
				"\ce{\\smash[bt]{2}}",
				[ '<mpadded height="0" depth="0">' ]
			],
			[
				"\ce{\\smash[tb]{2}}",
				[ '<mpadded height="0" depth="0">' ]
			],
			[
				"\ce{\\smash{2}}",
				[ '<mpadded height="0" depth="0"' ]
			],
		] );
	}

	/** @dataProvider provideTexVCCheckData */
	public function testTexVCCheck( string $input, array $output ) {
		$texVC = new TexVC();
		$options = [ "usemhchem" => true ];
		$warnings = [];
		$res = $texVC->check( $input, $options, $warnings );
		foreach ( $output as $value ) {
			$this->assertStringContainsString( $value, $res['input']->toMMLtree() );
		}
	}

	public static function provideNestedCe(): array {
		return [
			'ce in overbrace' => [ '\ce{\overbrace{\ce{H2O}}}', [ '<mi mathvariant="normal">H</mi>', '⏞' ] ],
			'ce in arrow label' => [
				'{\color{Blue}\ce{R^1-Sn(Alkyl)3}} + {\color{Red}\ce{R^2-X}} \ ' .
					'\ce{->[{\color{Green}\ce{Pd^0}}\text{ (catalytic)}][\text{ligand set}]} \ ' .
					'\overbrace{{\color{Blue}\ce{R^1}}\!-\!{\color{Red}\ce{R^2}}}^{coupled\ product}',
				[ '<mi mathvariant="normal">P</mi><mi mathvariant="normal">d</mi>' ]
			],
			'ce with a space' => [ '\ce {H2O}', [ '<mi mathvariant="normal">H</mi>' ] ],
			'ce with a space in overbrace' => [ '\ce{\overbrace{\ce {H2O}}}', [ '<mi mathvariant="normal">H</mi>' ] ],
			'pu' => [ '\pu{123 kJ/mol}', [ '<mi mathvariant="normal">k</mi>' ] ],
			'twenty levels' => [
				str_repeat( '\ce{\overbrace{', 20 ) . 'H2O' . str_repeat( '}}', 20 ),
				[ '<mi>H</mi><mn>2</mn><mi>O</mi>' ]
			],
		];
	}

	/** @dataProvider provideNestedCe */
	public function testNestedCe( string $input, array $output ) {
		$warnings = [];
		$res = ( new TexVC() )->check( $input, [ 'usemhchem' => true ], $warnings );
		$this->assertSame( '+', $res['status'] );
		$mml = (string)$res['input']->toMMLtree();
		$this->assertStringNotContainsString( 'mathcolor="red"', $mml,
			'The removed chem grammar showed what it could not render as red text' );
		$this->assertStringNotContainsString( '&lt;m', $mml, 'MathML must not end up escaped as text' );
		foreach ( $output as $value ) {
			$this->assertStringContainsString( $value, $mml );
		}
	}
}
