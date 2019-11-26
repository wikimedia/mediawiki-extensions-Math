<?php

/**
 * @covers \MathInputCheck
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathInputCheckTest extends MediaWikiTestCase {

	public function testAbstractClass() {
		$InputCheck = $this->getMockForAbstractClass( MathInputCheck::class );
		/** @var MathInputCheck $InputCheck */
		$this->assertFalse( $InputCheck->IsValid() );
		$this->assertNull( $InputCheck->getError() );
		$this->assertNull( $InputCheck->getValidTex() );
	}

}
