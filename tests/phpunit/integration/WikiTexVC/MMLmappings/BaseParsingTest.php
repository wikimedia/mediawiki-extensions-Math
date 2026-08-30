<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\LengthSpec;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Matrix;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing
 */
class BaseParsingTest extends MediaWikiIntegrationTestCase {

	public function testAccent() {
		$node = new Fun1(
			'\\widetilde',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::accent( $node, [], null, 'widetilde', '007E' );
		$this->assertStringContainsString( '~', $result );
		$this->assertStringContainsString( 'mover', $result );
	}

	public function testAccentArgPassing() {
		$node = new Fun1(
			'\\widetilde',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::accent( $node, [ 'k' => 'v' ], null, 'widetilde', '007E' );
		$this->assertStringContainsString( '<mi k="v"', $result );
	}

	public function testArray() {
		$node = new Matrix( 'matrix',
			new TexArray( new TexArray( new Literal( 'a' ) ) )
		);

		$result = BaseParsing::array( $node, [], null, 'array', '007E' );
		$this->assertStringContainsString( '<mi>a</mi>', (string)$result );
	}

	public function testBoldGreek() {
		$node = new Fun1(
			'\\boldsymbol',
			( new Literal( '\\alpha' ) )
		);
		$result = BaseParsing::boldsymbol( $node, [], null, 'boldsymbol' );
		$this->assertStringContainsString( '𝜶', $result );
	}

	public function testBoldSymbol() {
		$node = new Fun1(
			'\\boldsymbol',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::boldsymbol( $node, [], null, 'boldsymbol' );
		$this->assertStringContainsString( '𝒂', $result );
	}

	public function testCancel() {
		$node = new Fun1(
			'\\cancel',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::cancel( $node, [], null, 'cancel', 'something' );
		$this->assertStringContainsString( '<mi>a</mi><mrow class="menclose-something"></mrow>',
			$result );
		$this->assertStringContainsString( '<menclose notation="something" class="menclose">',
			$result );
	}

	public function testContextualGenFrac() {
		$node = new Fun2( '\\binom', new Literal( 'n' ), new Literal( 'k' ) );
		$result = BaseParsing::genFrac( $node, [], [], '\\binom', '(', ')', '0', '' );

		$this->assertStringContainsString( '<mfrac linethickness="0">', $result );
		$this->assertStringNotContainsString( '<mstyle', $result );
		$this->assertStringNotContainsString( 'minsize', $result );
		$this->assertStringNotContainsString( 'maxsize', $result );
	}

	public function testDbinomUsesDisplayStyle() {
		// amsmath defines \binom as \genfrac()\z@{} and \dbinom as \genfrac(){0pt}0:
		// https://archive.softwareheritage.org/swh:1:cnt:63c753577180436201384e6d5856e19b60994157;origin=https://github.com/latex3/latex2e;visit=swh:1:snp:b0ee00ac9658057bb387804cccae97bce8af59b5;anchor=swh:1:rev:cf95df5b80349c35628d021bf35418f5d844f2e5;path=/required/amsmath/amsmath.dtx;lines=630-641
		$node = new Fun2( '\\dbinom', new Literal( 'n' ), new Literal( 'k' ) );
		$operatorContent = [ 'styleargs' => [ 'displaystyle' => 'false' ] ];
		$result = BaseParsing::genFrac( $node, [], $operatorContent, '\\dbinom', '(', ')', '0', '0' );

		$this->assertStringContainsString( '<mstyle displaystyle="true" scriptlevel="0">', $result );
		$this->assertSame( 2, substr_count( $result, 'minsize="2.047em"' ) );
	}

	public function testContextualChoose() {
		$node = new Fun2( '\\choose', new Literal( 'n' ), new Literal( 'k' ) );
		$result = BaseParsing::over( $node, [], [], '\\choose' );

		$this->assertStringContainsString( '<mfrac linethickness="0">', $result );
		$this->assertStringContainsString( '<mo>(</mo>', $result );
		$this->assertStringContainsString( '<mo>)</mo>', $result );
		$this->assertStringNotContainsString( 'minsize', $result );
		$this->assertStringNotContainsString( 'maxsize', $result );
	}

	public function testUnderOver() {
		$node = new Fun1(
			'\\overline',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::underover( $node, [], null, '', '00AF' );
		$this->assertStringStartsWith( '<mrow', $result );
		$this->assertStringContainsString( 'mover', $result );
	}

	public function testUnderOverUnder() {
		$node = new Fun1(
			'\\underline',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::underover( $node, [], null, '', '00AF' );
		$this->assertStringContainsString( 'munder', $result );
	}

	public function testUnderOverDqUnder() {
		$node = new Fun1(
			'\\underline', new DQ(
			( new Literal( 'a' ) ),
			( new Literal( 'b' ) )
		) );
		$result = BaseParsing::underover( $node, [], null, '', '00AF' );
		$this->assertStringContainsString( 'munder', $result );
		$this->assertStringContainsString( 'mrow', $result );
	}

	public function testUnderOverInvalid() {
		$node = new Fun1(
			'\\someline',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::underover( $node, [], null, '', '00AF' );
		$this->assertStringContainsString( 'merror', $result );
	}

	public function testUnderArgPassing() {
		$node = new Fun1(
			'\\overline',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::underover( $node, [ 'k' => 'v' ], null, '', '00AF' );
		$this->assertStringContainsString( '<mi k="v"', $result );
	}

	public function testUnderBadArgPassing() {
		$node = new Fun1(
			'\\overline',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::underover( $node,
			[ 'k' => '"<script>alert("problem")</script>"' ], null, '', '00AF' );
		$this->assertStringContainsString( 'k=\'"&lt;script&gt;alert("problem")', $result );
	}

	public function testAlignAt() {
		$matrix = new Matrix( 'alignat',
			new TexArray( new TexArray( new Literal( '\\sin' ) ) )
		);
		$result = BaseParsing::alignAt( $matrix, [], null, 'alignat' );
		$this->assertStringContainsString( 'mtable', $result );
		$this->assertStringNotContainsString( 'padding-bottom', $result );
	}

	public function testAlignAtLength() {
		$row1 = new TexArray( new Literal( '\\sin' ) );
		$row1->setRowSpecs( new LengthSpec( '-', [ null, [ '2', '3' ] ], 'em' ) );
		$matrix = new Matrix( 'alignat',
			new TexArray(
			$row1
			)
		);
		$result = BaseParsing::alignAt( $matrix, [], null, 'alignat' );
		$this->assertStringContainsString( 'mtable', $result );
		$this->assertStringContainsString( 'padding-bottom', $result );
		$this->assertStringContainsString( '23em;', $result );
	}

	public function testHLineTop() {
		$matrix = new Matrix( 'matrix',
			new TexArray(
				new TexArray(
					new TexArray(
						new Literal( '\\hline ' ),
						new Literal( 'a' )
					)
				)
			)
		);
		$result = BaseParsing::matrix( $matrix, [], null, 'matrix', '002A' );
		$this->assertStringContainsString( 'top', $result );
	}

	public function testHLineBottom() {
		$matrix = new Matrix( 'matrix',
			new TexArray(
				new TexArray( new Literal( 'a' ) ),
				new TexArray( new TexArray( new Literal( '\\hline ' ) ) )
			)
		);
		$result = BaseParsing::matrix( $matrix, [], null, 'matrix', '002A' );
		$this->assertStringContainsString( 'bottom', $result );
		$this->assertStringContainsString( '<mi>a</mi>', $result );
	}

	public function testHLineLastLine() {
		$matrix = new Matrix( 'matrix',
			new TexArray(
				new TexArray( new Literal( 'a' ) ),
				new TexArray( new TexArray(
					new Literal( '\\hline ' ),
					new Literal( 'a' )
				) )
			)
		);
		$result = BaseParsing::matrix( $matrix, [], null, 'matrix', '002A' );
		$this->assertStringContainsString( 'class="mwe-math-matrix-top', $result );
		$this->assertStringContainsString( '<mi>a</mi>', $result );
	}

	public function testComplicatedHline() {
		$matrix = ( new TexVC() )->parse( '\\begin{array}{c}
\\hline a\\\\
\\hline 1\\\\
2\\\\
\\hline
\\end{array}' )[0];
		$result = BaseParsing::matrix( $matrix, [], null, 'matrix', '002A' );
		$this->assertStringContainsString( 'class="mwe-math-matrix-top"', $result );
		$this->assertStringContainsString( 'class="mwe-math-matrix-top mwe-math-matrix-bottom"', $result );
	}

	public function testMatrixIgnoreTrailingLine() {
		$node = new Matrix( 'matrix', new TexArray(
			new TexArray( new TexArray( new Literal( 'a' ) ) ),
			new TexArray( new TexArray() )
		) );
		$result = BaseParsing::array( $node, [], null, 'matrix', '002A' );
		$this->assertDoesNotMatchRegularExpression( '/<mtr>.*<\/mtr><mtr>.*<\/mtr>/', $result );
	}

	public function testMatrixDontIgnoreTrailingLineWhenNonEmptyCell() {
		$node = new Matrix( 'matrix', new TexArray(
			new TexArray( new TexArray( new Literal( 'a' ) ) ),
			new TexArray( new TexArray( new Literal( 'b' ) ) )
		) );
		$result = BaseParsing::array( $node, [], null, 'matrix', '002A' );
		$this->assertMatchesRegularExpression( '/<mtr>.*<\/mtr><mtr>.*<\/mtr>/', $result );
	}

	public function testMatrixDontIgnoreTrailingLineWhenTwoCells() {
		$node = new Matrix( 'matrix', new TexArray(
			new TexArray( new TexArray( new Literal( 'a' ) ) ),
			new TexArray( new TexArray(), new TexArray() )
		) );
		$result = BaseParsing::array( $node, [], null, 'matrix', '002A' );
		$this->assertMatchesRegularExpression( '/<mtr>.*<\/mtr><mtr>.*<\/mtr>/', $result );
	}

	public function testMatrixHlineIgnoreTrailingLine() {
		$node = new Matrix( 'matrix', new TexArray(
			new TexArray( new TexArray( new Literal( 'a' ) ) ),
			new TexArray( new TexArray( new Literal( '\\hline ' ) ) )
		) );
		$result = BaseParsing::array( $node, [], null, 'matrix', '002A' );
		$this->assertDoesNotMatchRegularExpression( '/<mtr>.*<\/mtr><mtr>.*<\/mtr>/', $result );
	}

	public function testMatrixHlineDontIgnoreTrailingLineWhenNonEmptyCell() {
		$node = new Matrix( 'matrix', new TexArray(
			new TexArray( new TexArray( new Literal( 'a' ) ) ),
			new TexArray( new TexArray( new Literal( '\\hline ' ), new Literal( 'b' ) ) )
		) );
		$result = BaseParsing::array( $node, [], null, 'matrix', '002A' );
		$this->assertMatchesRegularExpression( '/<mtr>.*<\/mtr><mtr>.*<\/mtr>/', $result );
	}

	public function testMatrixHlineDontIgnoreTrailingLineWhenTwoCells() {
		$node = new Matrix( 'matrix', new TexArray(
			new TexArray( new TexArray( new Literal( 'a' ) ) ),
			new TexArray( new TexArray( new Literal( '\\hline ' ) ), new TexArray() )
		) );
		$result = BaseParsing::array( $node, [], null, 'matrix', '002A' );
		$this->assertMatchesRegularExpression( '/<mtr>.*<\/mtr><mtr>.*<\/mtr>/', $result );
	}

	public function testHandleOperatorName() {
		$node = new Fun1(
			'\\operatorname',
			( new Literal( 'sn' ) )
		);
		$result = BaseParsing::handleOperatorName( $node, [], [
			"foundNamedFct" => [ true, true ]
		], 'operatorname' );
		$this->assertStringContainsString( 'sn</mi>', (string)$result );
		$this->assertStringContainsString( '<mo>&#x2061;</mo>', (string)$result );
	}

	public function testHandleOperatorLast() {
		$node = new Fun1(
			'\\operatorname',
			( new Literal( 'sn' ) )
		);
		$result = BaseParsing::handleOperatorName( $node, [], [
			"foundNamedFct" => [ true, false ]
		], 'operatorname' );
		$this->assertStringContainsString( 'sn</mi>', (string)$result );
		$this->assertStringNotContainsString( '<mo>&#x2061;</mo>', (string)$result );
	}

	public function testColumnSpecs() {
		$matrix = ( new TexVC() )->parse( '\\begin{array}{lcr}
z & = & a \\\\
f(x,y,z) & = & x + y + z
\\end{array}' )[0];
		$result = BaseParsing::matrix( $matrix, [], null, 'matrix', '002A' );
		$this->assertStringContainsString( '<mtd class="mwe-math-columnalign-l"', $result );
		$this->assertStringContainsString( '<mtd class="mwe-math-columnalign-r"', $result );
	}

	public function testSpace() {
		$node = new Literal( '\\ ' );
		$result = BaseParsing::macro( $node, [], [], '\\ ', '\\text{ }' );
		$this->assertStringContainsString( '<mtext>&#160;</mtext>', $result );
	}

	public function testIgnoreMisplacedLimit() {
		$node = new Literal( '\\limits ' );
		$result = $node->toMMLTree();
		$this->assertSame( '', (string)$result, 'Misplaced limits should be ignored' );
	}
}
