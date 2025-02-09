<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMappings;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMappings
 */
class BaseMappingsTest extends TestCase {

	public function testGetAll() {
		$all = BaseMappings::getAll();
		$this->assertIsArray( $all );
		$this->assertNotEmpty( $all );
	}

	public function provideTestCases(): array {
		// the second argument is an array of known problems which should be removed in the future
		return [
			'cancel' => [ 'cancel' ],
		];
	}

	/**
	 * @dataProvider provideTestCases
	 */
	public function testValidMethods( $setName, $knownProblems = [] ) {
		foreach ( BaseMappings::getAll()[$setName] as $symbol => $payload ) {
			$methodName = is_array( $payload ) ? $payload[0] : $payload;
			if ( in_array( $methodName, $knownProblems ) ) {
				continue;
			}
			$this->assertTrue( method_exists( BaseParsing::class, $methodName ),
				'Method ' . $methodName . ' for symbol ' . $symbol . ' does not exist in BaseParsing' );

		}
	}

	public function testGetOperatorByKey() {
		$this->assertEquals( '&#x221A;', TexUtil::getInstance()->operator_rendering( '\\surd' )[0] );
		$this->assertEquals( '&#x2212;', TexUtil::getInstance()->operator_rendering( '-' )[0] );
	}

	public function testGetColorByKey() {
		$this->assertEquals( '#ED1B23', BaseMappings::getColorByKey( 'red' )[0] );
	}

	public function testGetInstance() {
		$this->assertInstanceOf( BaseMappings::class, BaseMappings::getInstance() );
	}

	public function testGetCharacterByKey() {
		$this->assertEquals( '\u0393', BaseMappings::getCharacterByKey( '\\Gamma' ) );
	}

}
