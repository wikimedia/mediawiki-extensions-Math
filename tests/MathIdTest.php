<?php
/**
 * Test the Id feature
 * @group Math
 */
class MathIdTest extends MediaWikiTestCase {

	/**
	 * Checks if the id specified as attribute is set in the renderer object
	 */
	public function testBasics() {
		define( 'RANDOM_ID', 'a_random_id' );
		$real = MathRenderer::renderMath( "a+b", array( 'id' => RANDOM_ID ), MW_MATH_SOURCE );
		$this->assertEquals( RANDOM_ID, $real->getID() );
	}

}