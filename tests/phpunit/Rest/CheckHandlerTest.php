<?php

namespace Rest;

use MediaWiki\Extension\Math\Rest\CheckHandler;
use MediaWikiTestCase;

/**
 * Class CheckHandlerTest
 * @covers \MediaWiki\Extension\Math\Rest\CheckHandler
 * @package Rest
 */
class CheckHandlerTest extends MediaWikiTestCase {

	/**
	 * @covers \MediaWiki\Extension\Math\Rest\CheckHandler::getParamSettings
	 */
	public function testGetParamSettings() {
		$check = $this->getMockBuilder( CheckHandler::class )
			->disableOriginalConstructor()
			->setMethodsExcept( [ 'getParamSettings' ] )
			->getMock();
		$this->assertArrayHasKey( 'type', $check->getParamSettings() );
	}
}
