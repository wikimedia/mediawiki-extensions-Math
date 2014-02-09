<?php
/**
 * Generated test from the page https://en.wikipedia.org/wiki/Help:Displaying_a_formula
 *
 * @group Math
 */
class MathCoverageTest extends MediaWikiTestCase {

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