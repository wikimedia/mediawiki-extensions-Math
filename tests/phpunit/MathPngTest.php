<?php
/**
 * MediaWiki math extension
 *
 * @covers MathPng
 * @copyright 2002-2015 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */
class MathPngTest extends MediaWikiTestCase {

	/** @covers MathPng::__construct */
	public function testConstructor(){
		$renderer = new MathPng('a');
		$this->assertEquals('png', $renderer->getMode());
	}

	public function testOutput(){
		$renderer = $this->getMockBuilder( 'MathPng' )
			->setMethods( [ 'getFallbackImage' ] )
			->getMock();
		$renderer->expects( $this->once() )->method( 'getFallbackImage' );
		$renderer->getHtmlOutput();
	}
}