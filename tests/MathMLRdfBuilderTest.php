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
	const ACME_PREFIX_URL = 'http://acme/';
	const ACME_REF = 'testing';
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
		$writer->prefix( 'acme', self::ACME_PREFIX_URL );

		$writer->start();
		$writer->about( 'www', 'Q1' );

		$snak = new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( $test ) );
		$builder->addValue( $writer, 'acme', self::ACME_REF, 'DUMMY', $snak );

		return trim( $writer->drain() );
	}

	public function testValidInput() {
		$triples = $this->makeCase( 'a^2' );
		$this->assertContains( self::ACME_PREFIX_URL . self::ACME_REF . '> "<math', $triples );
		$this->assertContains( '<mi>a</mi>\n', $triples );
		$this->assertContains( '<mn>2</mn>\n', $triples );
		$this->assertContains( 'a^{2}', $triples );
		$this->assertContains( '^^<http://www.w3.org/1998/Math/MathML> .', $triples );
	}

	public function testInvalidInput() {
		$triples = $this->makeCase( '\notExists' );
		$this->assertContains( '<math', $triples );
		$this->assertContains( 'unknown function', $triples );
		$this->assertContains( 'notExists', $triples );
		$this->assertContains( '^^<http://www.w3.org/1998/Math/MathML> .', $triples );
	}
}
