<?php

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLDomVisitor;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmmultiscripts;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmn;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\VisitorFactory;

/**
 * Test the results of MathFormatter
 *
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLDomVisitor
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MMLDomVisitorTest extends MediaWikiIntegrationTestCase {

	public function testLeafNodeConversion() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [ 'mathvariant' => Variants::BOLD ], 'x' );
		$visitor->visit( $mi );
		$this->assertEquals(
			'<mi mathvariant="bold">x</mi>',
			$visitor->getHTML()
		);
	}

	public function testEmptyContainerConversion() {
		$visitor = new MMLDomVisitor();
		$mrow = new MMLmrow();
		$visitor->visit( $mrow );
		$this->assertEquals(
			'<mrow data-mjx-texclass="ORD"></mrow>',
			$visitor->getHTML()
		);
	}

	public function testGetTextFromLeafNode() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [], 'x' );
		$visitor->visit( $mi );
		$this->assertSame( 'x', $visitor->getText() );
	}

	public function testGetTextFromNestedStructure() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [], 'x' );
		$mo = new MMLmo( '', [], '=' );
		$mn = new MMLmn( '', [], '5' );
		$mrow = new MMLmrow( '', [], $mi, $mo, $mn );
		$visitor->visit( $mrow );
		$this->assertSame( 'x=5', $visitor->getText() );
	}

	public function testGetTextSkipsNullChildren() {
		$visitor = new MMLDomVisitor();
		$mmultiscripts = MMLmmultiscripts::newSubtree(
			new MMLmi( '', [], 'x' ),
			new MMLmn( '', [], '2' )
		);
		$mmultiscripts->accept( $visitor );
		$this->assertSame( 'x2', $visitor->getText() );
	}

	public function testEmptyElementReturnsEmptyString() {
		$visitor = new MMLDomVisitor();
		$emptyNode = new MMLbase( 'mrow' );
		$visitor->visit( $emptyNode );
		$this->assertSame( '', $visitor->getText() );
	}

	public function testStringCastLeafNode() {
		$visitor = new MMLDomVisitor();
		$mn = new MMLmn( '', [], '5' );
		$mn->accept( $visitor );
		$this->assertEquals(
			'<mn>5</mn>',
			$visitor->getHTML()
		);
	}

	public function testSpecialCharacterEscaping() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [], '<>&"' );
		$mi->accept( $visitor );
		$this->assertEquals(
			'<mi>&lt;&gt;&amp;"</mi>',
			$visitor->getHTML()
		);
	}

	public function testEscapedUnicode() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [], '&#x03B3;' );
		$mi->accept( $visitor );
		$this->assertEquals(
			'<mi>&#x03B3;</mi>',
			$visitor->getHTML()
		);
	}

	public function testNestedStructure() {
		$visitor = new MMLDomVisitor();
		$mi = new MMLmi( '', [], 'x' );
		$mo = new MMLmo( '', [], '+' );
		$mn = new MMLmn( '', [], '5' );
		$mrow = new MMLmrow( '', [], $mi, $mo, $mn );

		$expected = <<<XML
		<mrow>
		  <mi>x</mi>
		  <mo>+</mo>
		  <mn>5</mn>
		</mrow>
		XML;
		$visitor->visit( $mrow );
		$this->assertXmlStringEqualsXmlString( $expected, $visitor->getHTML() );
	}

	public function testDeepNesting() {
		$visitor = new MMLDomVisitor();
		$inner = new MMLmrow(
			'', [], new MMLmi( '', [], 'x' ),
			new MMLmo( '', [], '=' )
		);
		$outer = new MMLmrow( '', [], $inner, new MMLmn( '', [], '5' ) );
		$visitor->visit( $outer );
		$expected = <<<XML
		<mrow>
			<mrow>
				<mi>x</mi>
				<mo>=</mo>
			</mrow>
			<mn>5</mn>
		</mrow>
		XML;
		$this->assertXmlStringEqualsXmlString( $expected, $visitor->getHTML() );
	}

	public function testMMLarrayAsRoot() {
		$array = new MMLarray( new MMLmi( '', [], 'Hello' ), new MMLmi( '', [], 'World' ) );
		$html = (string)$array;
		$this->assertStringContainsString( '<mi>Hello</mi>', $html );
		$this->assertStringContainsString( '<mi>World</mi>', $html );
	}

	public function testMMLarrayAsChild() {
		$array = new MMLarray( new MMLmi( '', [], 'Inner' ) );
		$parent = new MMLbase( 'mroot', '', [], $array );
		$html = (string)$parent;
		$this->assertStringContainsString( '<mroot><mi>Inner</mi></mroot>', $html );
	}

	public function testEmptyMMLarray() {
		$array = new MMLarray();
		$html = (string)$array;
		$this->assertSame( '', $html );
	}

	public function testMixedChildrenInArray() {
		$array = new MMLarray(
			new MMLmi( '', [], 'x' ),
			new MMLmn( '', [], '3' )
		);
		$html = (string)$array;
		$this->assertStringContainsString( '<mi>x</mi>', $html );
		$this->assertStringContainsString( '<mn>3</mn>', $html );
	}

	public function testArrayRoot() {
		$array = new MMLarray(
			new MMLmi( '', [], 'x' ),
			new MMLmn( '', [], '3' )
		);
		$visitor = new MMLDomVisitor();
		$this->expectException( InvalidArgumentException::class );
		$visitor->visit( $array );
	}

	public function testGetTextContentWithLeafChildren() {
		$base = new MMLarray(
			new MMLmtext( '', [], 'first string' ),
			new MMLmtext( '', [], ' second string' ),
			new MMLmtext( '', [], ' third string' )
		);
		$this->assertSame( 'first string second string third string', $base->getTextContent() );
	}

	public function testGetTextContentWithMMLbaseChild() {
		$mockVisitor = $this->createMock( MMLDomVisitor::class );
		$mockVisitor->method( 'getText' )->willReturn( 'example text' );

		$visitorFactory = $this->createMock( VisitorFactory::class );
		$visitorFactory->method( 'createVisitor' )->willReturn( $mockVisitor );

		$mmlBaseChild = new MMLbase( 'test', '', [] );
		$mmlBaseChild->setVisitorFactory( $visitorFactory );

		$base = new MMLarray( $mmlBaseChild );
		$this->assertSame( 'example text', $base->getTextContent() );
	}

	public function testGetTextContentWithExceptionInChild() {
		$mockVisitor = $this->createMock( MMLDomVisitor::class );
		$mockVisitor->method( 'visit' )->willThrowException( new \DOMException( 'Test Exception' ) );
		$mockVisitor->method( 'getText' )->willReturn( 'example text' );

		$visitorFactory = $this->createMock( VisitorFactory::class );
		$visitorFactory->method( 'createVisitor' )->willReturn( $mockVisitor );

		$mmlBaseChild = new MMLbase( 'test', '', [] );
		$mmlBaseChild->setVisitorFactory( $visitorFactory );

		$base = new MMLarray( $mmlBaseChild, new MMLmtext( '', [], ' and another string' ) );
		$this->assertSame( ' and another string', $base->getTextContent() );
	}
}
