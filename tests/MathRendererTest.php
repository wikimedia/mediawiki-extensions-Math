<?php
/**
 * Test the database access and core functionallity of MathRenderer.
 * @group Math
 */
class MathRendererTest extends MediaWikiTestCase {
	const SOME_TEX		= "a+b";
	const SOME_HTML		= "a<sub>b</sub>";
	const SOME_MATHML	= "i⁢ℏ⁢∂t⁡Ψ=H^⁢Ψ<mrow><\ci>";

	/**
	 * creates a new database connection and a new math renderer
	 * TODO: Check if there is a way to get database access without creating
	 * the connection to the datbase explictly
	 * 	function addDBData() {
		$this->tablesUsed[] = 'math';
	}
	was not sufficant.
	 */
	protected function setup() {
		parent::setUp();
		//TODO:figure out why this is neccessary
		$this->db= wfGetDB( DB_SLAVE );
		//Create a new instance of MathSource
		$this->renderer=MathRenderer::getRenderer(self::SOME_TEX,array(),MW_MATH_SOURCE);
		//self::setupTestDB($this->db,"_test_math_");
		//$this->tablesUsed[] = 'math';
	}

	/**
	 * It seems as php can extend only one class at time. Therfore a way to access
	 * private and protected methods has to be found. Accorind to 
	 * http://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit
	 * this is the workaround.
	 * 
	 * //TODO: Check if this is the way to access private and protected methods
	 * @param unknown $name
	 * @return ReflectionMethod
	 */
	protected static function getMethod($name) {
		$class = new ReflectionClass('MathRenderer');
		$method = $class->getMethod($name);
		$method->setAccessible(true);
		return $method;
	}

	/**
	 * Checks the tex and hash functions
	 */
	public function testBasics(){
		//check if the TeX input was corretly passed to the class
		$this->assertEquals(self::SOME_TEX, $this->renderer->getTex() , "test getTex");
		//check the input hash computation
		$expectedhash=$this->db->encodeBlob( pack( "H32", md5( self::SOME_TEX ) ) );
		$this->assertEquals($expectedhash,$this->renderer->getInputHash());
	}

	/**
	 * Checks database access. Writes an etry and reads it back.
	 */
	public function testDB() {
		//set some values
		$this->renderer->html=self::SOME_HTML;
		$this->renderer->mathml=self::SOME_MATHML;
		//make writeDBEntry accessible
		$writeDBEntry=self::getMethod("writeDBEntry");
		//write values
		$writeDBEntry->invokeArgs($this->renderer, array());
		//create a new renderer object
		$renderer2=MathRenderer::getRenderer(self::SOME_TEX,array(),MW_MATH_SOURCE);
		//make read from db accessible
		$readFromDB=self::getMethod("readFromDB");
		//read the values
		$readFromDB->invoke($renderer2);
		//comparing the class object does now work due to null values etc.
		//$this->assertEquals($this->renderer,$renderer2);
		$this->assertEquals($this->renderer->getTex(),$renderer2->getTex());
		$this->assertEquals($this->renderer->mathml,$renderer2->mathml);
		$this->assertEquals($this->renderer->html,$renderer2->html);
	}

}