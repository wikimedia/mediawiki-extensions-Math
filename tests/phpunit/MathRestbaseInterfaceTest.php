<?php

use MediaWiki\Extension\Math\MathRestbaseInterface;
use MediaWiki\Extension\Math\Tests\MathMockHttpTrait;

/**
 * Test the interface to access Restbase paths
 * /media/math/check/{type}
 * /media/math/render/{format}/{hash}
 *
 * @covers \MediaWiki\Extension\Math\MathRestbaseInterface
 *
 * @group Math
 *
 * @license GPL-2.0-or-later
 */
class MathRestbaseInterfaceTest extends MediaWikiIntegrationTestCase {
	use MathMockHttpTrait;

	public function testSuccess() {
		$this->setupGoodMathRestBaseMockHttp();

		$input = '\\sin x^2';
		$rbi = new MathRestbaseInterface( $input );
		$this->assertTrue( $rbi->getSuccess(), "Assuming that $input is valid input." );
		$this->assertEquals( '\\sin x^{2}', $rbi->getCheckedTex() );
		$this->assertStringContainsString( '<mi>sin</mi>', $rbi->getMathML() );
		$this->assertStringContainsString( '/svg/RESOURCE_LOCATION', $rbi->getFullSvgUrl() );
		$this->assertStringContainsString( '/png/RESOURCE_LOCATION', $rbi->getFullPngUrl() );
	}

	public function testBatchEvaluate() {
		$body = [
			'success' => true,
			'checked' => 'CHECKED',
			'identifiers' => []
		];

		$response = [
			'code' => 200,
			'headers' => [],
			'body' => json_encode( $body )
		];

		$responses = [
			[ // for https://wikimedia.org/api/rest_v1/media/math/check/tex with input1
				'headers' => [ 'x-resource-location' => 'deadbeef1' ],
				'body' => json_encode( [ 'checked' => 'CHECKED1' ] + $body )
			] + $response,

			[ // for https://wikimedia.org/api/rest_v1/media/math/check/tex with input2
				'headers' => [ 'x-resource-location' => 'deadbeef2' ],
				'body' => json_encode( [ 'checked' => 'CHECKED2' ] + $body )
			] + $response,

			[ // for https://wikimedia.org/api/rest_v1/media/math/render/mml/deadbeef1
				'body' => 'MML1'
			] + $response,

			[ // for https://wikimedia.org/api/rest_v1/media/math/render/mml/deadbeef2
				'body' => 'MML2'
			] + $response,
		];

		$httpClient = $this->createNoOpMock( MultiHttpClient::class, [ 'runMulti' ] );
		$httpClient->method( 'runMulti' )->willReturnCallback(
			static function ( array $requests ) use ( &$responses ) {
				foreach ( $requests as &$req ) {
					$resp = array_shift( $responses );
					$req['response'] = $resp;
				}
				return $requests;
			}
		);

		$this->installMockHttp( $httpClient );

		// NOTE: Using fake response, the input is ignored.
		$rbi1 = new MathRestbaseInterface( 'input1' );
		$rbi2 = new MathRestbaseInterface( 'input2' );

		MathRestbaseInterface::batchEvaluate( [ $rbi1, $rbi2 ] );

		$this->assertTrue( $rbi1->getSuccess() );
		$this->assertEquals( 'CHECKED1', $rbi1->getCheckedTex() );
		$this->assertEquals( 'MML1', $rbi1->getMathML() );

		$this->assertTrue( $rbi2->getSuccess() );
		$this->assertEquals( 'CHECKED2', $rbi2->getCheckedTex() );
		$this->assertEquals( 'MML2', $rbi2->getMathML() );
	}

	public function testFail() {
		$this->setupBadMathRestBaseMockHttp();

		$input = '\\sin\\newcommand';
		$rbi = new MathRestbaseInterface( $input );
		$this->assertFalse( $rbi->getSuccess(), "Assuming that $input is invalid input." );
		$this->assertNull( $rbi->getCheckedTex() );
		$this->assertEquals( 'Illegal TeX function', $rbi->getError()->error->message );
	}

	public function testChem() {
		$this->setupGoodChemRestBaseMockHttp();

		$input = '\ce{H2O}';
		$rbi = new MathRestbaseInterface( $input, 'chem' );
		$this->assertTrue( $rbi->checkTeX(), "Assuming that $input is valid input." );
		$this->assertTrue( $rbi->getSuccess(), "Assuming that $input is valid input." );
		$this->assertEquals( '{\ce {H2O}}', $rbi->getCheckedTex() );
		$this->assertStringContainsString( '<msubsup>', $rbi->getMathML() );
		$this->assertStringContainsString( '<mtext>H</mtext>', $rbi->getMathML() );
	}

	public function testException() {
		$this->setupBadMathRestBaseMockHttp();

		$input = '\\sin\\newcommand';
		$rbi = new MathRestbaseInterface( $input );
		$this->expectException( MWException::class );
		$this->expectExceptionMessage( 'TeX input is invalid.' );
		$rbi->getMathML();
	}

	public function testExceptionSvg() {
		$this->setupBadMathRestBaseMockHttp();

		$input = '\\sin\\newcommand';
		$rbi = new MathRestbaseInterface( $input );
		$this->expectException( MWException::class );
		$this->expectExceptionMessage( 'TeX input is invalid.' );
		$rbi->getFullSvgUrl();
	}

	/**
	 * Incorporate the "details" in the error message, if the check requests passes, but the
	 * mml/svg/complete endpoints returns an error
	 */
	public function testLateError() {
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$input = '{"type":"https://mediawiki.org/wiki/HyperSwitch/errors/bad_request","title":"Bad Request","method":"POST","detail":["TeX parse error: Missing close brace"],"uri":"/complete"}';
		$this->expectException( MWException::class );
		$this->expectExceptionMessage( 'Cannot get mml. TeX parse error: Missing close brace' );
		MathRestbaseInterface::throwContentError( 'mml', $input );
	}

	/**
	 * Incorporate the "details" in the error message, if the check requests passes, but the
	 * mml/svg/complete endpoints returns an error
	 */
	public function testLateErrorString() {
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$input = '{"type":"https://mediawiki.org/wiki/HyperSwitch/errors/bad_request","title":"Bad Request","method":"POST","detail": "TeX parse error: Missing close brace","uri":"/complete"}';
		$this->expectException( MWException::class );
		$this->expectExceptionMessage( 'Cannot get mml. TeX parse error: Missing close brace' );
		MathRestbaseInterface::throwContentError( 'mml', $input );
	}

	public function testLateErrorNoDetail() {
		$input = '';
		$this->expectException( MWException::class );
		$this->expectExceptionMessage( 'Cannot get mml. Server problem.' );
		MathRestbaseInterface::throwContentError( 'mml', $input );
	}

	public function dataProviderForTestUrlUsedByCheckTeX() {
		$path = 'media/math/check/tex';

		yield 'Math FullRestbaseURL default case' => [
			[],
			[
				'url' => "https://wikimedia.org/api/rest_v1/$path",
				'method' => 'POST',
				'body' => [ 'type' => 'tex', 'q' => '\sin\newcommand' ]
			],
		];

		yield 'Math FullRestbaseURL case' => [
			[
				'MathFullRestbaseURL' => 'https://myWiki.test/'
			],
			[
				'url' => "https://myWiki.test/v1/$path",
				'method' => 'POST',
				'body' => [ 'type' => 'tex', 'q' => '\sin\newcommand' ]
			],
		];

		yield 'Internal Restbase URL case' => [
			[
				'MathUseInternalRestbasePath' => true,
				'VirtualRestConfig' => [
					'modules' => [
						'restbase' => [ 'url' => 'http://restbase.test.internal/api/' ]
					]
				],
				'MathFullRestbaseURL' => 'https://myWiki.test/'
			],
			[
				'url' => "http://restbase.test.internal/api/localhost/v1/$path",
				'method' => 'POST',
				'body' => [ 'type' => 'tex', 'q' => '\sin\newcommand' ]
			],
		];

		yield 'VisualEditor case' => [
			[
				'MathFullRestbaseURL' => null,
				'VisualEditorFullRestbaseURL' => "https://VisualEditor/api/rest_v1/"
			],
			[
				'url' => "https://VisualEditor/api/rest_v1/v1/$path",
				'method' => 'POST',
				'body' => [ 'type' => 'tex', 'q' => '\sin\newcommand' ]
			],
		];
	}

	/**
	 * @dataProvider dataProviderForTestUrlUsedByCheckTeX
	 */
	public function testUrlUsedByCheckTeX( array $config, array $expected ) {
		$response = [
			'headers' => [
				'x-resource-location' => 'deadbeef'
			],
			'body' => json_encode( [
				'success' => true,
				'checked' => 'who cares',
				'identifiers' => [],
			] )
		];

		$this->expectMathRestBaseMockHttpRequest( [ $expected ], [ $response ] );

		$this->overrideConfigValues( $config );

		$input = '\\sin\\newcommand';
		$rbi = new MathRestbaseInterface( $input );

		$rbi->checkTeX();
	}

	public function dataProviderForTestUrlUsedByGetML() {
		$path1 = 'media/math/check/tex';
		$path2 = 'media/math/render/mml/deadbeef';

		yield 'Math FullRestbaseURL default case' => [
			[],
			[
				[ 'url' => "https://wikimedia.org/api/rest_v1/$path1", 'method' => 'POST' ],
				[ 'url' => "https://wikimedia.org/api/rest_v1/$path2", 'method' => 'GET' ],
			],
		];

		yield 'Math FullRestbaseURL case' => [
			[
				'MathFullRestbaseURL' => 'https://myWiki.test/'
			],
			[
				[
					'url' => "https://myWiki.test/v1/$path1",
					'method' => 'POST'
				],
				[
					'url' => "https://myWiki.test/v1/$path2",
					'method' => 'GET'
				],
			],
		];

		yield 'Internal Restbase URL case' => [
			[
				'MathUseInternalRestbasePath' => true,
				'VirtualRestConfig' => [
					'modules' => [
						'restbase' => [ 'url' => 'http://restbase.test.internal/api/' ]
					]
				],
			],
			[
				[
					'url' => "http://restbase.test.internal/api/localhost/v1/$path1",
					'method' => 'POST'
				],
				[
					'url' => "http://restbase.test.internal/api/localhost/v1/$path2",
					'method' => 'GET'
				],
			],
		];

		yield 'VisualEditor case' => [
			[
				'MathFullRestbaseURL' => null,
				'VisualEditorFullRestbaseURL' => 'https://visual-editor.org/api/rest_v1/'
			],
			[
				[
					'url' => "https://visual-editor.org/api/rest_v1/v1/$path1",
					'method' => 'POST'
				],
				[
					'url' => "https://visual-editor.org/api/rest_v1/v1/$path2",
					'method' => 'GET'
				],
			],
		];
	}

	/**
	 * @dataProvider dataProviderForTestUrlUsedByGetML
	 */
	public function testUrlUsedByGetML( array $config, array $expectedList ) {
		$response1 = [
			'headers' => [
				'x-resource-location' => 'deadbeef'
			],
			'body' => json_encode( [
				'success' => true,
				'checked' => 'who cares',
				'identifiers' => [],
			] )
		];
		$response2 = [
			'body' => 'who cares'
		];

		$this->expectMathRestBaseMockHttpRequest( $expectedList, [ $response1, $response2 ] );

		$this->overrideConfigValues( $config );

		$input = '\\sin\\newcommand';
		$rbi = new MathRestbaseInterface( $input );

		$rbi->getMathML();
	}

	public function dataProviderForTestGetUrl() {
		$path = 'media/math/render/svg/2uejd9dj3jd';

		yield 'Math FullRestbaseURL default case' => [
			$path, false, [], 'https://wikimedia.org/api/rest_v1/media/math/render/svg/2uejd9dj3jd'
		];

		yield 'Math FullRestbaseURL case' => [ $path, false, [
			'MathFullRestbaseURL' => "https://myWiki.test/",
			'VisualEditorFullRestbaseURL' => 'VisualEditor/api/rest_'  // This should be ignored
		], 'https://myWiki.test/v1/media/math/render/svg/2uejd9dj3jd' ];

		yield 'VirtualRestConfig case' => [
			$path,
			true,
			[
				'MathUseInternalRestbasePath' => true,
				'VirtualRestConfig' => [
					'modules' => [ 'restbase' => [ 'url' => 'http://restbase.test.internal/api/' ] ]
				],
				'MathFullRestbaseURL' => "https://myWiki.test/",
				'VisualEditorFullRestbaseURL' => 'VisualEditor/api/rest_'  // This should be ignored
			],
			'http://restbase.test.internal/api/localhost/v1/media/math/render/svg/2uejd9dj3jd'
		];

		yield 'VirtualRestConfig case with Domain' => [
			$path,
			true,
			[
				'MathUseInternalRestbasePath' => true,
				'VirtualRestConfig' => [
					'modules' => [
						'restbase' => [
							'url' => 'http://restbase.test.internal/api',  // Should work with trailing slash '/'
							'domain' => 'testDomain'
						]
					]
				],
				'MathFullRestbaseURL' => "https://myWiki.test/",
				'VisualEditorFullRestbaseURL' => 'VisualEditor/api/rest_'  // This should be ignored
			],
			'http://restbase.test.internal/api/testDomain/v1/media/math/render/svg/2uejd9dj3jd'
		];

		yield 'VisualEditor case' => [
			$path,
			true,
			[
				'MathFullRestbaseURL' => null,
				'VisualEditorFullRestbaseURL' => 'VisualEditor/api/rest_'
			],
			'VisualEditor/api/rest_v1/media/math/render/svg/2uejd9dj3jd'
		];

		yield 'Exception case' => [ $path, false, [ 'MathFullRestbaseURL' => "", ], '', true ];
	}

	/**
	 * @dataProvider dataProviderForTestGetUrl
	 * @param string $path
	 * @param bool $internal
	 * @param array $config
	 * @param string $expected
	 * @param bool $expectingException
	 */
	public function testGetUrl( $path, $internal, $config, $expected, $expectingException = false ) {
		if ( $expectingException ) {
			$this->expectException( MWException::class );
		}
		$this->overrideConfigValues( $config );
		$input = '\\sin\\newcommand';
		$rbi = new MathRestbaseInterface( $input );
		$actual = $rbi->getUrl( $path, $internal );
		$this->assertSame( $expected, $actual );
	}
}
