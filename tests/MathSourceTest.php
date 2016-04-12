<?php

/**
 * Test the TeX source output format.
 *
 * @covers MathRenderer
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathSourceTest extends MediaWikiTestCase {

	/**
	 * Checks the basic functionallity
	 * i.e. if the span element is generated right.
	 */
	public function testBasics() {
		$real = MathRenderer::renderMath( "a+b", [], 'source' );
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
		$real = MathRenderer::renderMath( "a\n b", [], 'source' );
		$this->assertSame(
			'<span class="mwe-math-fallback-source-inline tex" dir="ltr">$ a  b $</span>',
			$real,
			"converting newlines to spaces"
		);
	}

}
