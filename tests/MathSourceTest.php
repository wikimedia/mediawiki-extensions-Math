<?php
/**
 * Test the TeX source output format.
 * @group Math
 */
class MathSourceTest extends MediaWikiTestCase {

	/**
	 * Checks the basic functionallity
	 * i.e. if the span element is generated right.
	 */
	public function testBasics() {
		$real = MathRenderer::renderMath( "a+b", array(), MW_MATH_SOURCE );
		$this->assertEquals(
			'<span class="mwe-math-fallback-source-inline tex" dir="ltr">$ a+b $</span>',
			$real,
			"Rendering of a+b in plain Text mode"
		);
	}

	/**
	 * Checks if newlines are converted to spaces correctly.
	 */
	public function testNewLines() {
		$real = MathRenderer::renderMath( "a\n b", array(), MW_MATH_SOURCE );
		$this->assertSame(
			'<span class="mwe-math-fallback-source-inline tex" dir="ltr">$ a  b $</span>',
			$real,
			"converting newlines to spaces"
		);
	}

}