<?php

/**
 * Test the database access and core functionality of MathRenderer.
 *
 * @covers MathRenderer
 *
 * @group Math
 * @group Database //Used by needsDB
 *
 * @licence GNU GPL v2+
 */
class MathDatabaseTest extends MediaWikiTestCase {
	/**
	 * @var MathRenderer
	 */
	private $renderer;
	const SOME_TEX = "a+b";
	const SOME_HTML = "a<sub>b</sub> and so on";
	const SOME_MATHML = "iℏ∂_tΨ=H^Ψ<mrow><\ci>";
	const SOME_CONSERVATIVENESS = 2;
	const SOME_OUTPUTHASH = 'C65c884f742c8591808a121a828bc09f8<';

	/**
	 * creates a new database connection and a new math renderer
	 * TODO: Check if there is a way to get database access without creating
	 * the connection to the database explicitly
	 * function addDBData() {
	 *    $this->tablesUsed[] = 'math';
	 * }
	 * was not sufficient.
	 */
	protected function setup() {
		parent::setUp();
		// TODO: figure out why this is necessary
		$this->db = wfGetDB( DB_MASTER );
		// Create a new instance of MathSource
		$this->renderer = new MathTexvc( self::SOME_TEX );
		$this->tablesUsed[] = 'math';
	}

	/**
	 * Checks the tex and hash functions
	 * @covers MathRenderer::getInputHash()
	 */
	public function testInputHash() {
		$expectedhash = $this->db->encodeBlob( pack( "H32", md5( self::SOME_TEX ) ) );
		$this->assertEquals( $expectedhash, $this->renderer->getInputHash() );
	}

	/**
	 * Helper function to set the current state of the sample renderer instance to the test values
	 */
	public function setValues() {
		// set some values
		$this->renderer->setTex( self::SOME_TEX );
		$this->renderer->setMathml( self::SOME_MATHML );
		$this->renderer->setHtml( self::SOME_HTML );
		$this->renderer->setOutputHash( self::SOME_OUTPUTHASH );
	}

	/**
	 * Checks database access. Writes an entry and reads it back.
	 * @covers MathRenderer::writeDatabaseEntry()
	 * @covers MathRenderer::readDatabaseEntry()
	 */
	public function testDBBasics() {
		$this->setValues();
		$this->renderer->writeToDatabase( $this->db );
		$renderer2 = new MathTexvc( self::SOME_TEX );
		$this->assertTrue( $renderer2->readFromDatabase(), 'Reading from database failed' );
		// comparing the class object does now work due to null values etc.
		$this->assertEquals(
			$this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same"
		);
		$this->assertEquals(
			$this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding"
		);
		$this->assertEquals(
			$this->renderer->getHtml(), $renderer2->getHtml(), 'test if HTML is the same'
		);
	}

	/**
	 * Checks the creation of the math table.
	 * @covers MathHooks::onLoadExtensionSchemaUpdates
	 */
	public function testCreateTable() {
		$this->setMwGlobals( 'wgMathValidModes', [ 'png' ] );
		$this->db->dropTable( "math", __METHOD__ );
		$dbu = DatabaseUpdater::newForDB( $this->db );
		$dbu->doUpdates( [ "extensions" ] );
		$this->expectOutputRegex( '/(.*)Creating math table(.*)/' );
		$this->setValues();
		$this->renderer->writeToDatabase();
		$res = $this->db->select( "math", "*" );
		$row = $res->fetchRow();
		$this->assertEquals( 10, count( $row ) );
	}

	/*
	 * This test checks if no additional write operation
	 * is performed, if the entry already existed in the database.
	 */
	public function testNoWrite() {
		$this->setValues();
		$inputHash = $this->renderer->getInputHash();
		$this->assertTrue( $this->renderer->isChanged() );
		$this->assertTrue( $this->renderer->writeCache(), "Write new entry" );
		$res = $this->db->selectField( "math", "math_inputhash",
			[ "math_inputhash" => $inputHash ] );
		$this->assertTrue( $res !== false, "Check database entry" );
		$this->assertTrue( $this->renderer->readFromDatabase(), "Read entry from database" );
		$this->assertFalse( $this->renderer->isChanged() );
		// modify the database entry manually
		$this->db->delete( "math", [ "math_inputhash" => $inputHash ] );
		// the renderer should not be aware of the modification and should not recreate the entry
		$this->assertFalse( $this->renderer->writeCache() );
		// as a result no entry can be found in the database.
		$this->assertFalse( $this->renderer->readFromDatabase() );

	}
}
