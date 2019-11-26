<?php

/**
 * @covers MathInputCheck
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathInputCheckTest extends MediaWikiTestCase {
	/**
	 * @covers MathInputCheck::isValid
	 */
	public function testIsValid() {
		$InputCheck = $this->getMockBuilder( MathInputCheck::class )->getMock();
		$this->assertFalse( $InputCheck->IsValid() );
	}

	/**
	 * @covers MathInputCheck::getError
	 * @todo   Implement testGetError().
	 */
	public function testGetError() {
		$InputCheck = $this->getMockBuilder( MathInputCheck::class )->getMock();
		$this->assertNull( $InputCheck->getError() );
	}

	/**
	 * @covers MathInputCheck::getValidTex
	 */
	public function testGetValidTex() {
		$InputCheck = $this->getMockBuilder( MathInputCheck::class )
			->setConstructorArgs( [ 'some tex input' ] )
			->getMock();
		$this->assertNull( $InputCheck->getValidTex() );
	}
}
