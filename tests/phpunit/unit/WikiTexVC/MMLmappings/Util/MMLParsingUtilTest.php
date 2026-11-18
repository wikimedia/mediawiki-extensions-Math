<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWikiUnitTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil
 */
class MMLParsingUtilTest extends MediaWikiUnitTestCase {

	public function testInvalidColor() {
		$result = MMLParsingUtil::parseDefineColorExpression( "INVALID" );
		$this->assertNull( $result );
	}

	public function testRGBOne() {
		$result = MMLParsingUtil::parseDefineColorExpression(
			"\\definecolor {ultramarine}{rgb}{0,0.12549019607843,0.37647058823529}" );
		$this->assertEquals( 'ultramarine', $result['name'] );
		$this->assertEquals( 'rgb', $result['type'] );
		$this->assertEquals( '#002060', $result['hex'] );
	}

	public function testInvalidColorString() {
		$result = MMLParsingUtil::parseDefineColorExpression(
			"\\definecolor {gray}{0.123}" );
		$this->assertNull( $result );
	}

	public static function provideMathrmInside() {
		return [
			'\\mathbf' => [ Variants::BOLD, Variants::NORMAL ],
			'\\boldsymbol' => [ Variants::BOLDITALIC, Variants::BOLDITALIC ],
			'no font' => [ null, Variants::NORMAL ],
		];
	}

	/**
	 * @dataProvider provideMathrmInside
	 */
	public function testMathrmInside( ?string $outer, string $expected ) {
		$passedArgs = $outer === null ? [] : [ 'mathvariant' => $outer ];
		$result = MMLParsingUtil::getFontArgs( '\\mathrm', null, $passedArgs );
		$this->assertSame( [ 'mathvariant' => $expected ], $result );
	}

	public function testAddAttributesToFirstElement() {
		$a = new MMLmi( '', [], 'a' );
		$b = new MMLmi( '', [], 'b' );
		MMLParsingUtil::addAttributesToFirstElement( new MMLarray( new MMLarray( $a, $b ) ),
			[ 'k' => 'v', 'skipped' => null ] );
		$this->assertSame( [ 'k' => 'v' ], $a->getAttributes() );
		$this->assertSame( [], $b->getAttributes() );
	}
}
