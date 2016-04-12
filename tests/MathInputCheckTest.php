<?php

/**
 * @covers MathInputCheck
 *
 * @group Math
 *
 * @licence GNU GPL v2+
 */
class MathInputCheckTest extends MediaWikiTestCase
{
	/**
	 * @covers MathInputCheck::isValid
	 */
	public function testIsValid() {
		$InputCheck = $this->getMockBuilder( 'MathInputCheck' )->getMock();
		$this->assertEquals( $InputCheck->IsValid(), false );
	}

	/**
	 * @covers MathInputCheck::getError
	 * @todo   Implement testGetError().
	 */
	public function testGetError() {
		$InputCheck = $this->getMockBuilder( 'MathInputCheck' )->getMock();
		$this->assertNull( $InputCheck->getError() );
	}

	/**
	 * @covers MathInputCheck::getValidTex
	 */
	public function testGetValidTex() {
		$InputCheck = $this->getMockBuilder( 'MathInputCheck' )
			->setConstructorArgs( [ 'some tex input' ] )
			->getMock();
		$this->assertNull( $InputCheck->getValidTex() );
	}
}
