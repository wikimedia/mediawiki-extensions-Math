<?php
/**
 * Test the MathML RDF formatter
 *
 * @group Math
 * @covers MathMLRdfBuilder
 * @author Moritz Schubotz (physikerwelt)
 */

use DataValues\StringValue;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikimedia\Purtle\NTriplesRdfWriter;

class MathMLRdfBuilderTest extends MediaWikiTestCase {
	const VADLID_TEX = "a^2+b^2=c^2";
	const INVADLID_TEX = "\\notExists";

	protected static $hasRestbase;

	public static function setUpBeforeClass() {
		$rbi = new MathRestbaseInterface();
		self::$hasRestbase = $rbi->checkBackend( true );
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();
		if ( !self::$hasRestbase ) {
			$this->markTestSkipped( "Can not connect to Restbase Math interface." );
		}
	}

	/**
	 *
	 * @param string $test
	 * @return string
	 */
	private function makeCase( $test ) {
		$builder = new MathMLRdfBuilder();
		$writer = new NTriplesRdfWriter();
		$writer->prefix( 'www', "http://www/" );
		$writer->prefix( 'acme', "http://acme/" );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( $test ) );
		$builder->addValue( $writer, 'acme', 'testing', 'DUMMY', $snak );

		return trim( $writer->drain() );
	}

	public function testValidInput() {
		$triples = $this->makeCase( self::VADLID_TEX );
		$this->assertContains( '<math', $triples );
		$this->assertContains( '<mi>a</mi>', $triples );
		$this->assertContains( 'a^{2}+b^{2}=c^{2}', $triples );
		$this->assertContains( '^^<http://www.w3.org/1998/Math/MathML> .', $triples );
	}

	public function testInvalidInput() {
		$triples = $this->makeCase( self::INVADLID_TEX );
		$this->assertContains( '<math', $triples );
		$this->assertContains( 'unknown function', $triples );
		$this->assertContains( 'notExists', $triples );
		$this->assertContains( '^^<http://www.w3.org/1998/Math/MathML> .', $triples );
	}
}
