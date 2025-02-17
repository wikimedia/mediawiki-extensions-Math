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
			'macros' => [ 'macros', [ 'SetSize', 'Overunderset', 'Root', 'MoveRoot', 'LeftRight', 'MoveLeftRight',
				'rule', 'Rule', 'Nonscript', 'BuildRel', 'FBox', 'FrameBox', 'Strut', 'Cr', 'HFill', 'BeginEnd',
				'HandleLabel', 'HandleRef', 'HandleNoTag', 'MmlToken' ]
			],
			'cancel' => [ 'cancel' ],
			'custom' => [ 'custom', [ 'Insert' ] ]
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

	public function testGetCancelByKey() {
		$this->assertEquals( 'updiagonalstrike', BaseMappings::getCancelByKey( '\\cancel' )[1] );
		$this->assertEquals( 'cancelTo', BaseMappings::getCancelByKey( '\\cancelto' )[0] );
		$this->assertNull( BaseMappings::getCancelByKey( '\\notCancel' ) );
	}

	public function testGetOperatorByKey() {
		$this->assertEquals( '&#x221A;', TexUtil::getInstance()->operator_rendering( '\\surd' )[0] );
		$this->assertEquals( '&#x2212;', TexUtil::getInstance()->operator_rendering( '-' )[0] );
	}

	public function testGetCustomByKey() {
		$this->assertEquals( '\u222E', BaseMappings::getCustomByKey( '\\oint' )[1] );
	}

	public function testGetMacroByKey() {
		$this->assertEquals( 'D', BaseMappings::getMacroByKey( '\\displaystyle' )[1] );
	}

	public function testGetSpaceMacroByKey() {
		$this->assertEquals( '\\text{ }', BaseMappings::getMacroByKey( '\\ ' )[1] );
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
