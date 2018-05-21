<?php

/**
 * @covers \MathPng
 *
 * @license GPL-2.0-or-later
 */
class MathPngTest extends MediaWikiTestCase {

	public function testConstructor() {
		$renderer = new MathPng( 'a' );

		$this->assertEquals( 'png', $renderer->getMode() );
	}

	public function testOutput() {
		$renderer = $this->getMockBuilder( MathPng::class )
			->setMethods( [ 'getFallbackImage' ] )
			->getMock();
		$renderer->method( 'getFallbackImage' )
			->willReturn( '<FALLBACK>' );

		$this->assertSame( '<FALLBACK>', $renderer->getHtmlOutput() );
	}

}
