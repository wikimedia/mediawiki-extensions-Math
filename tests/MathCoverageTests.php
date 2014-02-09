<?php
/**
 * Generated test from the page https://en.wikipedia.org/wiki/Help:Displaying_a_formula
 *
 * @group Math
 */
class MathCoverageTest extends MediaWikiTestCase {
	public function testDataProviderAnnotation(){
		$this->testCoverage("1",'<img class="tex" alt="1" src="/images/math/c/4/c/c4ca4238a0b923820dcc509a6f75849b.png" />');
	}

	/**
	 * @dataProvider provider
	 */
	public function testCoverage($input, $output)
	{
		$this->assertEquals($output,  MathRenderer::renderMath( $input , array(), MW_MATH_PNG ), "Failed to render $input");
	}

	public function provider()
	{
		$parserTests = unserialize( file_get_contents( dirname( __FILE__ ) .'/ParserTest.data' ) );
		$testData = array();
		//TODO: use array functions of PHP
		foreach ($parserTests as $input => $output) {
			$testData[] = array ( $input, $output);
		}
		return $testData;
	}
}