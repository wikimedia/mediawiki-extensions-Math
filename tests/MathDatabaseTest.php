<?php
/**
 * Test the database access and core functionallity of MathRenderer.
*
* @group Math
* @group Database //Used by needsDB
*/
class MathDatabaseTest extends MediaWikiTestCase {
	/** MathRenderer */
	private $renderer;
	const SOME_TEX = "a+b";
	const SOME_MATHML = "iℏ∂_tΨ=H^Ψ<mrow><\ci>";
	const SOME_LOG = "Sample Log Text.";
	const SOME_STATUSCODE = 2;
	const SOME_TIMESTAMP = 1272509157;
	// const SOME_PNG = "PNG";
	// const SOME_SVG = "<?xml </svg >>%%LIKE;'\" DROP TABLE math;";
	const SOME_VALIDXML = true;
	const NUM_BASIC_FIELDS = 5;
	const NUM_DEBUG_FIELDS = 3;

	/**
	 * creates a new database connection and a new math renderer
	 * TODO: Check if there is a way to get database access without creating
	 * the connection to the datbase explictly
	 * function addDBData() {
	 * 	$this->tablesUsed[] = 'math';
	 * }
	 * was not sufficant.
	 */
	protected function setup() {
		global $wgMathDebug;
		parent::setUp();
		// TODO:figure out why this is neccessary
		$this->db = wfGetDB( DB_MASTER );
		// Create a new instance of MathSource
		$this->renderer = $this->getMockForAbstractClass( 'MathRenderer', array ( self::SOME_TEX ) );
		MathHooks::onParserTestTables( $this->tablesUsed );
		self::setupTestDB( $this->db, "mathtest" );

		$wgMathDebug = FALSE;
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
	 * Helper function to set the current state of the sample renderer istance to the test values
	 */
	public function setValues() {
		$this->renderer->setTex( self::SOME_TEX );
		$this->renderer->setMathml( self::SOME_MATHML );
	}

	/**
	 * Checks database access. Writes an etry and reads it back.
	 * @convers MathRenderer::writeDatabaseEntry()
	 * @convers MathRenderer::readDatabaseEntry()
	 */
	public function testInsertUpdate() {
		global $wgMathDebug;
		if ( $this->db->getType() === 'sqlite' ) {
			$this->markTestSkipped( "SQLite has global indices. We cannot " .
					"create the `unitest_math` table, its math_inputhash index " .
					"would conflict with the one from the real `math` table."
			);
		}
		$wgMathDebug = false;
		// Set some values
		$this->setValues();
		$this->assertFalse($this->renderer->isInDatabase() , 'New values should not be in DB.');
		$this->db->begin();
		$this->renderer->writeCache();
		$this->assertTrue( $this->db->writesOrCallbacksPending() , 'Expects a pending callback');
		$this->db->commit();
		$this->assertFalse($this->db->writesOrCallbacksPending(), 'Expect all changed to be commited to the database.');
		$this->assertContains("INSERT IGNORE INTO", $this->db->lastQuery(), 'Expect that the new record was inserted' );
		$this->assertTrue($this->renderer->isInDatabase());
		$this->assertFalse($this->renderer->isChanged());
		$this->db->begin();
		$this->renderer->writeCache();
		$this->assertFalse($this->db->writesOrCallbacksPending());
		$this->db->rollback();
		$renderer2 = $this->getMockForAbstractClass( 'MathRenderer', array ( self::SOME_TEX ) );
		$renderer2->readFromDatabase();
		$this->assertTrue($renderer2->isInDatabase());
		// comparing the class object does now work due to null values etc.
		// $this->assertEquals($this->renderer,$renderer2);
		$this->assertEquals( $this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same" );
		$this->assertEquals( $this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding" );
		$this->assertFalse( $renderer2->isChanged() );
		$renderer2->setMathML("dummyMathML");
		$this->assertTrue( $renderer2->isChanged() );
		$this->assertTrue($renderer2->isInDatabase());
		$renderer2->writeToDatabase();
		$this->assertContains('UPDATE', $this->db->lastQuery());
	}

	/**
	 * Checks database access. Writes an etry and reads it back.
	 * @convers MathRenderer::writeDatabaseEntry()
	 * @convers MathRenderer::readDatabaseEntry()
	 */
	public function testDBBasics() {
		global $wgMathDebug;
		if ( $this->db->getType() === 'sqlite' ) {
			$this->markTestSkipped( "SQLite has global indices. We cannot " .
					"create the `unitest_math` table, its math_inputhash index " .
					"would conflict with the one from the real `math` table."
			);
		}
		// ;
		$this->setValues();
		$wgMathDebug = false;
		$this->renderer->writeCache();
		$renderer2 = $this->getMockForAbstractClass( 'MathRenderer', array ( self::SOME_TEX ) );
		$renderer2->readFromDatabase();
		$this->assertTrue($renderer2->isInDatabase());
		// comparing the class object does now work due to null values etc.
		// $this->assertEquals($this->renderer,$renderer2);
		$this->assertEquals( $this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same" );
		$this->assertEquals( $this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding" );
		$this->assertFalse( $renderer2->isChanged() );
		$renderer2->setMathML("dummyMathML");
		$this->assertTrue( $renderer2->isChanged() );
		$this->assertTrue($renderer2->isInDatabase());
		$renderer2->writeToDatabase();
		$this->assertContains('UPDATE', $this->db->lastQuery());
	}

	/**
	 * Checks the creation of the math table without debugging endabled.
	 * @covers MathHooks::onLoadExtensionSchemaUpdates
	 */
	public function testBasicCreateTable() {
		if ( $this->db->getType() === 'sqlite' ) {
			$this->markTestSkipped( "SQLite has global indices. We cannot " .
				"create the `unitest_math` table, its math_inputhash index " .
				"would conflict with the one from the real `math` table."
			);
		}
		global $wgMathDebug;
		$this->db->dropTable( "math", __METHOD__ );
		$this->db->dropTable( "mathoid", __METHOD__ );
		$wgMathDebug = false;
		$dbu = DatabaseUpdater::newForDB( $this->db );
		$dbu->doUpdates( array( "extensions" ) );
		$this->expectOutputRegex( '/(.*)Creating math table(.*)/' );
		$this->expectOutputRegex( '/(.*)Creating mathoid table(.*)/' );
		$this->setValues();
		$this->renderer->writeToDatabase();
		$res = $this->db->select( "mathoid", "*" );
		$row = $res->fetchRow();
		$this->assertEquals( 2 * self::NUM_BASIC_FIELDS,  sizeof( $row ) );
	}
}