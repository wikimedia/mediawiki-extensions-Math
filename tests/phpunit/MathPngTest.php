<?php

/**
 * @covers MathPng
 *
 * @license GPL-2.0-or-later
 */
class MathPngTest extends MediaWikiTestCase {

	/** @var string The fallback image html tag.*/
	const FALLBACK = '<img src="test.png" />';

	public function testConstructor() {
		$renderer = new MathPng( 'a' );

		$this->assertEquals( 'png', $renderer->getMode() );
	}

	public function testOutput() {
		
		$renderer = $this->getMockBuilder( MathPng::class )
			->setMethods( [ 'getFallbackImage' ] )
			->getMock();
		$renderer->method( 'getFallbackImage' )
			->willReturn( self::FALLBACK );

		$this->assertSame( self::FALLBACK, $renderer->getHtmlOutput() );
	}

}
