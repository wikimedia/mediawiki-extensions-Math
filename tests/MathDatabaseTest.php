<?php
/**
 * Test the database access and core functionallity of MathRenderer.
*
* @group Math
* @group Database //Used by needsDB
*/
class MathDatabaseTest extends MediaWikiTestCase {
	var $renderer;
	const SOME_TEX = "a+b";
	const SOME_HTML = "a<sub>b</sub>";
	const SOME_MATHML = "i⁢ℏ⁢∂t⁡Ψ=H^⁢Ψ<mrow><\ci>";
	const SOME_LOG = "Sample Log Text.";
	const SOME_STATUSCODE = 2;
	const SOME_TIMESTAMP = 1272509157;
	const SOME_VALIDXML = true;
	const NUM_BASIC_FIELDS = 5;
	const NUM_DEBUG_FIELDS = 5;

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
		global $wgDebugMath;
		parent::setUp();
		// TODO:figure out why this is neccessary
		$this->db = wfGetDB( DB_MASTER );
		// Create a new instance of MathSource
		$this->renderer = $this->getMockForAbstractClass( 'MathRenderer', array ( self::SOME_TEX ) );
		$this->tablesUsed[] = 'math';
		self::setupTestDB( $this->db, "mathtest" );

		$wgDebugMath = FALSE;
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
		// set some values
		$this->renderer->setTex( self::SOME_TEX );
		$this->renderer->setHtml( self::SOME_HTML );
		$this->renderer->setMathml( self::SOME_MATHML );
		$this->renderer->setLog( self::SOME_LOG );
		$this->renderer->setStatusCode( self::SOME_STATUSCODE );
		$this->renderer->setTimestamp( self::SOME_TIMESTAMP );
		// $this->renderer->validxml = self::SOME_VALIDXML;
	}
	/**
	 * Checks database access. Writes an etry and reads it back.
	 * @convers MathRenderer::writeDatabaseEntry()
	 * @convers MathRenderer::readDatabaseEntry()
	 */
	public function testDBBasics() {
		// ;
		$this->setValues();
		$wgDebugMath = false;

		$this->renderer->writeToDatabase();

		$renderer2 = $this->getMockForAbstractClass( 'MathRenderer', array ( self::SOME_TEX ) );
		$renderer2->readFromDatabase();
		// comparing the class object does now work due to null values etc.
		// $this->assertEquals($this->renderer,$renderer2);
		$this->assertEquals( $this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same" );
		$this->assertEquals( $this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding" );
		$this->assertEquals( $this->renderer->getHtml(), $renderer2->getHtml() );
	}



	/**
	 * Checks the creation of the math table without debugging endabled.
	 * @covers MathHooks::onLoadExtensionSchemaUpdates
	 */
	public function testBasicCreateTable() {
		if( $this->db->getType() === 'sqlite' ) {
			$this->markTestSkipped( "SQLite has global indices. We cannot " .
				"create the `unitest_math` table, its math_inputhash index " .
				"would conflict with the one from the real `math` table."
			);
		}
		global $wgDebugMath;
		$this->db->dropTable( "math", __METHOD__ );
		$wgDebugMath = false;
		$dbu = DatabaseUpdater::newForDB( $this->db );
		$dbu->doUpdates( array( "extensions" ) );
		$this->expectOutputRegex( '/(.*)Creating math table(.*)/' );
		$this->setValues();
		$this->renderer->writeToDatabase();
		$res = $this->db->select( "math", "*" );
		$row = $res->fetchRow();
		$this->assertEquals( sizeof( $row ), 2 * self::NUM_BASIC_FIELDS );
	}

	/**
	 * Checks the creation of the math table with debugging endabled.
	 * @covers MathHooks::onLoadExtensionSchemaUpdates
	 */
	public function testDebugCreateTable() {
		global $wgDebugMath;
		sleep( 1 ); // see https://bugzilla.wikimedia.org/show_bug.cgi?id=45194
		$this->db->dropTable( "math", __METHOD__ );
		$wgDebugMath = true;
		$dbu = DatabaseUpdater::newForDB( $this->db );
		$dbu->doUpdates( array( "extensions" ) );
		$this->expectOutputRegex( '/(.*)Creating math table(.*)/' );
		$this->setValues();
		$this->renderer->writeDatabaseEntry();
		$res = $this->db->select( "math", "*" );
		$row = $res->fetchRow();
		$this->assertEquals( sizeof( $row ), 2 * ( self::NUM_BASIC_FIELDS + self::NUM_DEBUG_FIELDS ) );
	}
	
	/**
	 * Checks database access. Writes an etry and reads it back.
	 * @convers MathRenderer::writeDatabaseEntry()
	 * @convers MathRenderer::readDatabaseEntry()
	 * @depends testDebugCreateTable
	 */
	public function testDBDebug() {
		global $wgDebugMath;
		// ;
		$this->setValues();
		$wgDebugMath = true;
	
		$this->renderer->writeDatabaseEntry();
	
		$renderer2 = $this->getMockForAbstractClass( 'MathRenderer', array ( self::SOME_TEX ) );
		$renderer2->readDatabaseEntry();
		// comparing the class object does now work due to null values etc.
		// $this->assertEquals($this->renderer,$renderer2);
		$this->assertEquals( $this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same" );
		$this->assertEquals( $this->renderer->mathml, $renderer2->mathml, "Check MathML encoding" );
		$this->assertEquals( $this->renderer->html, $renderer2->html ,"test html");
		$this->assertEquals( $this->renderer->log, $renderer2->log , "test log");
		$this->assertEquals( $this->renderer->statusCode, $renderer2->statusCode , "test status code");
	}
	

}