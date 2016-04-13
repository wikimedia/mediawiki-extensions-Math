<?php

/**
 * Test the Id feature
 *
 * @covers MathRenderer
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathIdTest extends MediaWikiTestCase {

	/**
	 * Checks if the id specified as attribute is set in the renderer object
	 */
	public function testBasics() {
		define( 'RANDOM_ID', 'a_random_id' );
		$renderer = MathRenderer::getRenderer( "a+b", [ 'id' => RANDOM_ID ] );
		$this->assertEquals( RANDOM_ID, $renderer->getId() );
	}

}
