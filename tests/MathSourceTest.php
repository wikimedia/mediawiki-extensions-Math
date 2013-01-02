<?php
/**
 * @group Math
 */
class MathSourceTest extends MediaWikiTestCase {
        
        public function testPlainText(){
        	$real=MathRenderer::renderMath("a+b",array(),MW_MATH_SOURCE);
        	$this->assertEquals('<span class="tex" dir="ltr">$ a+b $</span>', $real, "Rendering of a+b in plain Text mode");

        }
        public function testNewLines(){
        	$real=MathRenderer::renderMath("a\n b",array(),MW_MATH_SOURCE);
        	$this->assertSame('<span class="tex" dir="ltr">$ a  b $</span>', $real, "converting newlines to spaces");
        }
        
}