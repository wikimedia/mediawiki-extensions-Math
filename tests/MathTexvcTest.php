<?php
/**
 * PHPUnit tests for MathTexvc.
 *
 * @group Extensions
 * @group Math
 */

/**
 * @covers MathTexvc
 */
class MathTexvcTest extends MediaWikiTestCase {

	/**
	 * Tests behavior of render() upon a cache hit.
	 * If the rendered object exists in the database cache, MathTexvc
	 * just generates HTML from it, and skips shelling out to texvc
	 * entirely.
	 * @covers MathTexvc::render
	 */
	function testRenderCacheHit() {
		global $wgMathCheckFiles;

		// Disable file checks. (This is permissable, because PHPUnit
		// will backup / restore global state on test setup / teardown.)
		$wgMathCheckFiles = false;

		// Create a MathTexvc mock, replacing methods 'readFromDatabase',
		// 'callTexvc', and 'doHTMLRender' with test doubles.
		$texvc = $this->getMockBuilder( 'MathTexvc' )
			->setMethods( array( 'readFromDatabase', 'callTexvc', 'doHTMLRender' ) )
			->disableOriginalConstructor()
			->getMock();

		// When we call render() below, MathTexvc will ...

		// ... first check if the item exists in the database cache:
		$texvc->expects( $this->once() )
			->method( 'readFromDatabase' )
			->with()
			->will( $this->returnValue( true ) );

		// ... if cache lookup succeeded, it won't shell out to texvc:
		$texvc->expects( $this->never() )
			->method( 'callTexvc' );

		// ... instead, MathTexvc will skip to HTML generation:
		$texvc->expects( $this->once() )
			->method( 'doHTMLRender' );

		$texvc->render();
	}

	/**
	 * Test behavior of render() upon cache miss.
	 * If the rendered object is not in the cache, MathTexvc will shell
	 * out to texvc to generate it. If texvc succeeds, it'll use the
	 * result to generate HTML.
	 * @covers MathTexvc::render
	 */
	function testRenderCacheMiss() {
		$texvc = $this->getMockBuilder( 'MathTexvc' )
			->setMethods( array( 'readCache', 'callTexvc', 'doHTMLRender' ) )
			->disableOriginalConstructor()
			->getMock();

		// When we call render() below, MathTexvc will ...

		// ... first look up the item in cache:
		$texvc->expects( $this->once() )
			->method( 'readCache' )
			->will( $this->returnValue( false ) );

		// ... on cache miss, MathTexvc will shell out to texvc:
		$texvc->expects( $this->once() )
			->method( 'callTexvc' )
			->will( $this->returnValue( MathTexvc::MW_TEXVC_SUCCESS ) );

		// ... if texvc succeeds, MathTexvc will generate HTML:
		$texvc->expects( $this->once() )
			->method( 'doHTMLRender' );

		$texvc->render();
	}

	/**
	 * Test behavior of render() when texvc fails.
	 * If texvc returns a value other than MW_TEXVC_SUCCESS, render()
	 * returns the error object and does not attempt to generate HTML.
	 * @covers MathTexvc::render
	 */
	function testRenderTexvcFailure() {
		$texvc = $this->getMockBuilder( 'MathTexvc' )
			->setMethods( array( 'readCache', 'callTexvc', 'doHTMLRender' ) )
			->disableOriginalConstructor()
			->getMock();

		// When we call render() below, MathTexvc will ...

		// ... first look up the item in cache:
		$texvc->expects( $this->any() )
			->method( 'readCache' )
			->will( $this->returnValue( false ) );

		// ... on cache miss, shell out to texvc:
		$texvc->expects( $this->once() )
			->method( 'callTexvc' )
			->will( $this->returnValue( 'error' ) );

		// ... if texvc fails, render() will not generate HTML:
		$texvc->expects( $this->never() )
			->method( 'doHTMLRender' );

		// ... it will return the error result instead:
		$this->assertEquals( $texvc->render(), 'error' );
	}

	/**
	 * Tests behavior of convertTexvcError
	 *
	 * @covers MathTexvc::convertTexvcError
	 */
	public function testConvertTexvcError() {
		$texvc = $this->getMockBuilder( 'MathTexvc' )
			->setMethods(NULL)
			->disableOriginalConstructor()
			->getMock();

		$mathFailure = wfMessage( 'math_failure' )->inContentLanguage()->escaped();

		$actualLexing = $texvc->convertTexvcError( 'E' );
		$expectedLexing = wfMessage( 'math_lexing_error', '' )->inContentLanguage()->escaped();
		$this->assertContains( $mathFailure, $actualLexing, 'Lexing error contains general math failure message' );
		$this->assertContains( $expectedLexing, $actualLexing, 'Lexing error contains detailed error for lexing' );

		$actualSyntax = $texvc->convertTexvcError( 'S' );
		$expectedSyntax = wfMessage( 'math_syntax_error', '' )->inContentLanguage()->escaped();
		$this->assertContains( $mathFailure, $actualSyntax, 'Syntax error contains general math failure message' );
		$this->assertContains( $expectedSyntax, $actualSyntax, 'Syntax error contains detailed error for syntax' );

		$unknownFunction = 'figureEightIntegral';
		$actualUnknownFunction = $texvc->convertTexvcError( "F$unknownFunction" );
		$expectedUnknownFunction = wfMessage( 'math_unknown_function', $unknownFunction )->inContentLanguage()->escaped();
		$this->assertContains( $mathFailure, $actualUnknownFunction, 'Unknown function error contains general math failure message' );
		$this->assertContains( $expectedUnknownFunction, $actualUnknownFunction, 'Unknown function error contains detailed error for unknown function' );

		$actualUnknownError = $texvc->convertTexvcError( 'Q' );
		$expectedUnknownError = wfMessage( 'math_unknown_error', '' )->inContentLanguage()->escaped();
		$this->assertContains( $mathFailure, $actualUnknownError, 'Unknown error contains general math failure message' );
		$this->assertContains( $expectedUnknownError, $actualUnknownError, 'Unknown error contains detailed error for unknownError' );
	}
}
