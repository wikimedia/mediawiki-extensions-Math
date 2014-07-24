<?php
/**
 * @group Math
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
			->setConstructorArgs( array( 'some tex input' ) )
			->getMock();
		$this->assertNull( $InputCheck->getValidTex() );
	}
}
