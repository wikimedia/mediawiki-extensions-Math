<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\AMSMappings;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\AMSMappings
 */
class AmsMappingsTest extends TestCase {

	public function testGetMacroByKey() {
		$this->assertEquals( 'Tilde', AMSMappings::getMacroByKey( '\\nobreakspace' )[0] );
	}

	public function testGetInstance() {
		$this->assertInstanceOf( AMSMappings::class, AMSMappings::getInstance() );
	}

	public function testGetEnvironmentByKey() {
		$this->assertEquals( 'array', AMSMappings::getEnvironmentByKey( 'vmatrix' )[0] );
	}

}
