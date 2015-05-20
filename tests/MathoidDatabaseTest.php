<?php
/**
* Test the database access and core functionality of MathRenderer.
*
* @group Math
* @group Database (Used by needsDB)
*/
class MathoidDatabaseTest extends MediaWikiTestCase {
	public $renderer;
	const SOME_TEX = "a+b";
	const SOME_HTML = "a<sub>b</sub>";
	const SOME_MATHML = "iℏ∂_tΨ=H^Ψ<mrow><\ci>";
	const SOME_LOG = "Sample Log Text.";
	const SOME_TIMESTAMP = 1272509157;
	const SOME_SVG = "<?xml </svg >>%%LIKE;'\" DROP TABLE mathoid;";
	const SOME_PNG = <<<'PNG'

PNG;


	/**
	 * Helper function to test protected/private Methods
	 * @param $name
	 * @return ReflectionMethod
	 */
	protected static function getMethod( $name ) {
		$class = new ReflectionClass( 'MathMathML' );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}


	/**
	 * creates a new database connection and a new math renderer
	 * the connection to the database explicitly
	 * function addDBData() {
	 * 	$this->tablesUsed[] = 'mathoid';
	 * }
	 * was not sufficient.
	 */
	protected function setup() {
		parent::setUp();
		$this->db = wfGetDB( DB_MASTER );
		$this->renderer = new MathMathML( self::SOME_TEX );
		self::setupTestDB( $this->db, "mathtest" );
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
	}

	/**
	 * @covers MathLaTeXML::getMathTableName
	 */
	public function testTableName() {
		$fnGetMathTableName = self::getMethod( 'getMathTableName' );
		$obj = new MathLaTeXML();
		$tableName = $fnGetMathTableName->invokeArgs( $obj, array() );
		$this->assertEquals( $tableName, "mathoid", "Wrong latexml table name" );
	}

	/**
	 * Checks the creation of the math table without debugging enabled.
	 * @covers MathHooks::onLoadExtensionSchemaUpdates
	 */
	public function testCreateTable() {
		$this->setMwGlobals( 'wgMathValidModes', array( MW_MATH_MATHML ) );
		$this->db->dropTable( "mathoid", __METHOD__ );
		$dbu = DatabaseUpdater::newForDB( $this->db );
		$dbu->doUpdates( array( "extensions" ) );
		$this->expectOutputRegex( '/(.*)Creating mathoid table(.*)/' );
		$this->setValues();
		$this->renderer->writeToDatabase();
		$res = $this->db->select( "mathoid", "*" );
		$row = $res->fetchRow();
		$this->assertEquals( 16,  sizeof( $row ) );
	}

	/**
	 * Checks database access. Writes an entry and reads it back.
	 * @depends testCreateTable
	 * @covers MathRenderer::writeDatabaseEntry()
	 * @covers MathRenderer::readDatabaseEntry()
	 */
	public function testDBBasics() {
		$this->setValues();
		$this->renderer->writeToDatabase();

		$renderer2 = $this->renderer = new MathLaTeXML( self::SOME_TEX );
		$renderer2->readFromDatabase();
		// comparing the class object does now work due to null values etc.
		$this->assertEquals( $this->renderer->getTex(), $renderer2->getTex(), "test if tex is the same" );
		$this->assertEquals( $this->renderer->getMathml(), $renderer2->getMathml(), "Check MathML encoding" );
		$this->assertEquals( $this->renderer->getPng(), $renderer2->getPng(), "png images differ" );
	}


}
