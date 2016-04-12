<?php

/**
 * Test the LaTeXML output format.
 *
 * @covers MathLaTeXML
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathLaTeXMLTest extends MediaWikiTestCase {

	/**
	 * Tests the serialization of the LaTeXML settings
	 * @covers MathLaTeXML::serializeSettings
	 */
	public function testSerializeSettings() {
		$renderer = $this->getMockBuilder( 'MathLaTeXML' )
			->setMethods( null )
			->disableOriginalConstructor()
			->getMock();
		$sampleSettings = [
			'k1' => 'v1',
			'k2&=' => 'v2 + & *üö',
			'k3' => [
				'v3A', 'v3b'
			]
		];
		$expected = 'k1=v1&k2%26%3D=v2+%2B+%26+%2A%C3%BC%C3%B6&k3=v3A&k3=v3b';
		$this->assertEquals(
			$expected,
			$renderer->serializeSettings( $sampleSettings ),
			'test serialization of array settings'
		);
		$this->assertEquals(
			$expected,
			$renderer->serializeSettings( $expected ),
			'test serialization of a string setting'
		);
	}
}
