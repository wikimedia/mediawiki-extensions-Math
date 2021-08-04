<?php

/**
 * Test the Id feature
 *
 * @covers \MediaWiki\Extension\Math\MathRenderer
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathIdTest extends MediaWikiIntegrationTestCase {

	/**
	 * Checks if the id specified as attribute is set in the renderer object
	 */
	public function testBasics() {
		define( 'RANDOM_ID', 'a_random_id' );
		$renderer = $this->getServiceContainer()
			->get( 'Math.RendererFactory' )
			->getRenderer( "a+b", [ 'id' => RANDOM_ID ] );
		$this->assertEquals( RANDOM_ID, $renderer->getID() );
	}

}
