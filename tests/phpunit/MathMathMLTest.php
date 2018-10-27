<?php


/**
 * Test the MathML output format.
 *
 * @covers MathMathML
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathMathMLTest extends MediaWikiTestCase {

	// State-variables for HTTP Mockup classes
	public static $content = null;
	public static $good = false;
	public static $html = false;
	public static $timeout = false;

	/**
	 * Set the mock values for the HTTP Mockup classes
	 *
	 * @param bool $good
	 * @param mixed $html HTML of the error message or false if no error is present.
	 * @param bool $timeout true if
	 */
	public static function setMockValues( $good, $html, $timeout ) {
		self::$good = $good;
		self::$html = $html;
		self::$timeout = $timeout;
	}

	protected function setUp() {
		parent::setUp();
		$this->setMwGlobals( 'wgMathoidCli', false );
	}

	/**@covers MathMathML::__constructor */
	public function testMathMLConstructorWithPmml() {
		$mml = new MathMathML( '<mo>sin</mo>', [ 'type' => 'pmml' ] );
		$this->assertEquals( 'pmml', $mml->getInputType() );
		$this->assertEquals( '<math><mo>sin</mo></math>', $mml->getMathml() );
	}

	/**@covers MathMathML::__constructor */
	public function testMathMLConstructorWithInvalidType() {
		$mml = new MathMathML( '<mo>sin</mo>', [ 'type' => 'invalid' ] );
		$this->assertEquals( 'tex', $mml->getInputType() );
	}

	/**@covers MathMathML::__constructor */
	public function testChangeRootElemts() {
		$mml = new MathMathML( '<mo>sin</mo>', [ 'type' => 'invalid' ] );
		$mml->setAllowedRootElements( [ 'a','b' ] );
		$this->assertEquals( [ 'a','b' ], $mml->getAllowedRootElements() );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Invalid request.
	 * @covers MathMathML::makeRequest
	 */
	public function testMakeRequestInvalid() {
		self::setMockValues( false, false, false );
		$url = 'http://example.com/invalid';

		$renderer = $this->getMockBuilder( MathMathML::class )
				->setMethods( null )
				->disableOriginalConstructor()
				->getMock();

		/** @var MathMathML $renderer */
		$requestReturn = $renderer->makeRequest( $url, 'a+b', $res, $error,
			MathMLHttpRequestTester::class );
		$this->assertEquals( false, $requestReturn,
			"requestReturn is false if HTTP::post returns false." );
		$this->assertEquals( false, $res,
			"res is false if HTTP:post returns false." );
		$errmsg = wfMessage( 'math_invalidresponse', '', $url, '' )->inContentLanguage()->escaped();
		$this->assertContains( $errmsg, $error,
			"return an error if HTTP::post returns false" );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Valid request.
	 * @covers MathMathML::makeRequest
	 */
	public function testMakeRequestSuccess() {
		self::setMockValues( true, true, false );
		self::$content = 'test content';
		$url = 'http://example.com/valid';
		$renderer = $this->getMockBuilder( MathMathML::class )
				->setMethods( null )
				->disableOriginalConstructor()
				->getMock();

		/** @var MathMathML $renderer */
		$requestReturn = $renderer->makeRequest( $url, 'a+b', $res, $error,
			MathMLHttpRequestTester::class );
		$this->assertEquals( true, $requestReturn, "successful call return" );
		$this->assertEquals( 'test content', $res, 'successful call' );
		$this->assertEquals( $error, '', "successful call error-message" );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Testcase: Timeout.
	 * @covers MathMathML::makeRequest
	 */
	public function testMakeRequestTimeout() {
		self::setMockValues( false, true, true );
		$url = 'http://example.com/timeout';
		$renderer = $this->getMockBuilder( MathMathML::class )
				->setMethods( null )
				->disableOriginalConstructor()
				->getMock();

		/** @var MathMathML $renderer */
		$requestReturn = $renderer->makeRequest(
			$url, '$\longcommand$', $res, $error, MathMLHttpRequestTester::class
		);
		$this->assertEquals( false, $requestReturn, "timeout call return" );
		$this->assertEquals( false, $res, "timeout call return" );
		$errmsg = wfMessage( 'math_timeout', '', $url )->inContentLanguage()->escaped();
		$this->assertContains( $errmsg, $error, "timeout call errormessage" );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Test case: Get PostData.
	 * @covers MathMathML::makeRequest
	 */
	public function testMakeRequestGetPostData() {
		self::setMockValues( false, true, true );
		$url = 'http://example.com/timeout';
		$renderer = $this->getMockBuilder( MathMathML::class )
			->setMethods( [ 'getPostData' ] )
			->disableOriginalConstructor()
			->getMock();
		$renderer->expects( $this->once() )->method( 'getPostData' );

		/** @var MathMathML $renderer */
		$renderer->makeRequest( $url, false, $res, $error, MathMLHttpRequestTester::class );
	}

	/**
	 * Tests behavior of makeRequest() that communicates with the host.
	 * Test case: Get host.
	 * @covers MathMathML::pickHost
	 */
	public function testMakeRequestGetHost() {
		self::setMockValues( false, true, true );
		$url = 'http://example.com/timeout';
		$renderer = $this->getMockBuilder( MathMathML::class )
			->setMethods( [ 'getPostData', 'pickHost' ] )
			->disableOriginalConstructor()
			->getMock();
		$renderer->expects( $this->once() )->method( 'pickHost' );

		/** @var MathMathML $renderer */
		$renderer->makeRequest( false, false, $res, $error, MathMLHttpRequestTester::class );
	}

	/**
	 * Checks if a String is a valid MathML element
	 * @covers MathMathML::isValidMathML
	 */
	public function testisValidMathML() {
		$renderer = $this->getMockBuilder( MathMathML::class )
				->setMethods( null )
				->disableOriginalConstructor()
				->getMock();
		$validSample = '<math>content</math>';
		$invalidSample = '<notmath />';
		$this->assertTrue( $renderer->isValidMathML( $validSample ),
			'test if math expression is valid mathml sample' );
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
	}

	/**
	 * @covers MathMathML::isValidMathML
	 */
	public function testInvalidXml() {
		$renderer = $this->getMockBuilder( MathMathML::class )
			->setMethods( null )
			->disableOriginalConstructor()
			->getMock();
		$invalidSample = '<mat';
		$this->assertFalse( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
		$renderer->setXMLValidation( false );
		$this->assertTrue( $renderer->isValidMathML( $invalidSample ),
			'test if math expression is invalid mathml sample' );
	}

	public function testintegrationTestWithLinks() {
		$p = new Parser();
		$po = new ParserOptions();
		$t = new Title( "test" );
		$res = $p->parse( '[[test|<math forcemathmode="png">a+b</math>]]', $t, $po )->getText();
		$this->assertContains( '</a>', $res );
		$this->assertContains( 'png', $res );
	}

	/**
	 * @covers MathMathML::correctSvgStyle
	 * @see https://phabricator.wikimedia.org/T132563
	 */
	public function testMathMLStyle() {
		$m = new MathMathML();
		$m->setSvg( 'style="vertical-align:-.505ex" height="2.843ex" width="28.527ex"' );
		$style = '';
		$m->correctSvgStyle( $style );
		$this->assertEquals( 'vertical-align:-.505ex; height: 2.843ex; width: 28.527ex;', $style );
		$m->setSvg( 'style=" vertical-align:-.505ex; \n" height="2.843ex" width="28.527ex"' );
		$this->assertEquals( 'vertical-align:-.505ex; height: 2.843ex; width: 28.527ex;', $style );
	}

	public function testPickHost() {
		$hosts = [ 'a', 'b', 'c' ];
		$this->setMwGlobals( 'wgMathMathMLUrl', $hosts );
		$class = new ReflectionClass( MathMathML::class );
		$method = $class->getMethod( 'pickHost' );
		$method->setAccessible( true );
		srand( 0 ); // Make array_rand always return the same elements
		$h1 = $hosts[array_rand( $hosts )];
		$h2 = $hosts[array_rand( $hosts )];
		srand( 0 );
		$m = new MathMathML();
		$host1 = $method->invoke( $m, [] );
		$this->assertEquals( $h1, $host1 );
		$host2 = $method->invoke( $m, [] );
		$this->assertEquals( $host1, $host2 );
		$m2 = new MathMathML();
		$host3 = $method->invoke( $m2, [] );
		$this->assertEquals( $h2, $host3 );
	}

	public function testWarning() {
		$this->setMwGlobals( "wgMathDisableTexFilter", 'always' );
		$renderer = new MathMathML();
		$rbi = $this->getMockBuilder( MathRestbaseInterface::class )
			->setMethods( [ 'getWarnings', 'getSuccess' ] )
			->setConstructorArgs( [ 'a+b' ] )
			->getMock();
		$rbi->method( 'getWarnings' )->willReturn( [ (object)[ 'type' => 'mhchem-deprecation' ] ] );
		$rbi->method( 'getSuccess' )->willReturn( true );
		$renderer->setRestbaseInterface( $rbi );
		$renderer->render();
		$parser = $this->getMockBuilder( Parser::class )
			->setMethods( [ 'addTrackingCategory' ] )
			->disableOriginalConstructor()
			->getMock();
		$parser->method( 'addTrackingCategory' )->willReturn( true );
		$parser->expects( $this->once() )
			->method( 'addTrackingCategory' )
			->with( 'math-tracking-category-mhchem-deprecation' );
		$renderer->addTrackingCategories( $parser );
	}
}

/**
 * Helper class for testing
 * @author physikerwelt
 * @see MWHttpRequestTester
 */
class MathMLHttpRequestTester {

	public static function factory() {
		return new self();
	}

	public static function execute() {
		return new MathMLTestStatus();
	}

	public static function getContent() {
		return MathMathMLTest::$content;
	}

}

/**
 * Helper class for testing
 * @author physikerwelt
 * @see Status
 */
class MathMLTestStatus {

	static function isGood() {
		return MathMathMLTest::$good;
	}

	static function hasMessage( $s ) {
		if ( $s == 'http-timed-out' ) {
			return MathMathMLTest::$timeout;
		} else {
			return false;
		}
	}

	static function getHtml() {
		return MathMathMLTest::$html;
	}

	static function getWikiText() {
		return MathMathMLTest::$html;
	}

}
