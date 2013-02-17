<?php
/**
 * Test the database access and core functionallity of MathRenderer.
 * 
 * @group Math
 */
class MathRendererTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();
		//TODO:figure out why this is neccessary
		$this->db= wfGetDB( DB_SLAVE );
		//self::setupTestDB($this->db,"_test_math_");
		//$this->tablesUsed[] = 'math';
	
	}
	function addDBData() {
		$this->tablesUsed[] = 'math';
	}
	/**
	 * Checks the basic functionallity
	 * i.e. if the span element is generated right.
	 */
	public function testBasics(){
		$real=MathRenderer::getRenderer("a+b",array(),MW_MATH_SOURCE);
		$this->assertEquals('a+b', $real->getTex() , "test getTex");
		$expectedhash=$this->db->encodeBlob( pack( "H32", md5( 'a+b' ) ) );
		$this->assertEquals($expectedhash,$real->getInputHash());
	}

}