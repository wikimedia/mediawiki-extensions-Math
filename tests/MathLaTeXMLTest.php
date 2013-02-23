<?php
/**
 * @group Math
 */
class MathLaTeXMLTest extends MediaWikiTestCase {

	public function testPlainText(){
		$real=MathRenderer::renderMath("a+b",array(),MW_MATH_LATEXML);
		//$this->assertEquals('<span class="tex" dir="ltr">$ a+b $</span>', $real, "Rendering of a+b in plain Text mode");
	}

}