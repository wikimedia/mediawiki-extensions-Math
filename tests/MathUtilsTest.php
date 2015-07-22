<?php
/**
 * Test Math utility functions.
 *
 * @group Math
 */
class MathUtilsTest extends MediaWikiTestCase {

	public function testMathModeToString() {
		$default = 'png-testing'; // use a different string for testing only
		$testCases = array(
			'MW_MATH_SIMPLE'      => $default,
			'MW_MATH_HTML'        => $default,
			'MW_MATH_MODERN'      => $default,
			'MW_MATH_MATHJAX'     => $default,
			'MW_MATH_LATEXML_JAX' => $default,
			'MW_MATH_PNG'         => 'png',
			'MW_MATH_SOURCE'      => 'source',
			'MW_MATH_MATHML'      => 'mathml',
			'MW_MATH_LATEXML'     => 'latexml',
			1                     => $default,
			2                     => $default,
			4                     => $default,
			6                     => $default,
			8                     => $default,
			0                     => 'png',
			3                     => 'source',
			5                     => 'mathml',
			7                     => 'latexml'
		);
		foreach ( $testCases as $input => $expected ){
			$real = MathHooks::mathModeToString( $input, $default );
			$this->assertEquals( $expected, $real, "Conversion math mode" );
		}
	}

	public function testMathStyleToString() {
		$default = 'inlineDisplaystyle-test';
		$testCases = array(
			'MW_MATHSTYLE_INLINE_DISPLAYSTYLE'  => 'inlineDisplaystyle',
			'MW_MATHSTYLE_DISPLAY'              => 'display',
			'MW_MATHSTYLE_INLINE'               => 'inline',
			0                                   => 'inlineDisplaystyle',
			1                                   => 'display',
			2                                   => 'inline',
		);
		foreach ( $testCases as $input => $expected ){
			$real = MathHooks::mathStyleToString( $input, $default );
			$this->assertEquals( $expected, $real, "Conversion in math style" );
		}
	}

	public function testMathCheckToString() {
		$default = 'always-default';
		$testCases = array(
			'MW_MATH_CHECK_ALWAYS'  => 'always',
			'MW_MATH_CHECK_NEVER'   => 'never',
			'MW_MATH_CHECK_NEW'     => 'new',
			0                       => 'always',
			1                       => 'never',
			2                       => 'new',
		);

		foreach ( $testCases as $input => $expected ){
			$real = MathHooks::mathCheckToString( $input, $default );
			$this->assertEquals( $expected, $real, "Conversion in math check method" );
		}
	}

}