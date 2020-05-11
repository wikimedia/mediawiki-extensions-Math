<?php

namespace Rest;

use ExtensionRegistry;
use GuzzleHttp\Psr7\Uri;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Extension\Math\InputCheck\MathoidChecker;
use MediaWiki\Extension\Math\Rest\Check;
use MediaWiki\Permissions\PermissionManager;
use MediaWiki\Rest\BasicAccess\StaticBasicAuthorizer;
use MediaWiki\Rest\RequestData;
use MediaWiki\Rest\RequestInterface;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\ResponseFactory;
use MediaWiki\Rest\Router;
use MediaWiki\Rest\Validator\Validator;
use Psr\Container\ContainerInterface;
use User;
use Wikimedia\ObjectFactory;

class CheckTest extends \MediaWikiTestCase {

	/**
	 * Trivial test
	 * @covers \MediaWiki\Extension\Math\Rest\Check::getParamSettings
	 */
	public function testGetParamSettings() {
		$check = new Check();
		$this->assertArrayHasKey( 'type', $check->getParamSettings() );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\Rest\Check::execute
	 */
	public function testExecute() {
		$check = new Check();
		$this->expectException( 'LogicException' );
		$check->execute();
	}

	private function getValidator( RequestInterface $request, $objectFactory ) {
		$permissionManager = $this->createMock( PermissionManager::class );

		return new Validator( $objectFactory, $permissionManager, $request, new User );
	}

	/** @return Router */
	private function createRouter( RequestInterface $request ) {
		$objectFactory =
			new ObjectFactory( $this->getMockForAbstractClass( ContainerInterface::class ) );
		$restValidator = $this->getValidator( $request, $objectFactory );
		// one can not create a router without files
		$routeFiles = [ __DIR__ . '/testRoutes.json' ];

		return new Router( $routeFiles,
			ExtensionRegistry::getInstance()->getAttribute( 'RestRoutes' ),
			'http://wiki.example.com', '/rest', new \EmptyBagOStuff(), new ResponseFactory( [] ),
			new StaticBasicAuthorizer( null ), $objectFactory, $restValidator );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\Rest\Check::checkPreconditions
	 */
	public function testCheckPreconditionsSinX() {
		$testInput = '\\sin (x)';
		$testType = 'tex';
		$expectedStatus = 200;
		$expectedBody = 'something';
		$this->setFakeChecker( $expectedStatus, $expectedBody, $testInput, $testType );
		$check = new Check();
		$request = $this->createMock( RequestData::class );
		$request->expects( $this->once() )
			->method( 'getPathParam' )
			->with( 'type' )
			->willReturn( $testType );
		$request->expects( $this->once() )
			->method( 'getPostParams' )
			->willReturn( [ 'q' => $testInput ] );
		$responseFactory = $this->createMock( ResponseFactory::class );
		$response = new Response();
		$responseFactory->expects( $this->once() )->method( 'create' )->willReturn( $response );
		$fakeRouter = $this->createRouter( $request );
		$check->init( $fakeRouter, $request, [], $responseFactory );
		$check->checkPreconditions();
		$this->assertSame( 200, $response->getStatusCode() );
		$body = $response->getBody()->getContents();
		$this->assertEquals( $expectedBody, $body );
	}

	/**
	 * Test that only post requests are allowed
	 *
	 * @covers \MediaWiki\Extension\Math\Rest\Check::checkPreconditions
	 */
	public function testWrongMethod() {
		$request = new RequestData( [
			'uri' => new Uri( '/rest/math/v0/check/tex' ),
		] );
		$router = $this->createRouter( $request );
		$response = $router->execute( $request );
		$this->assertSame( 405, $response->getStatusCode() );
		$this->assertSame( 'Method Not Allowed', $response->getReasonPhrase() );
		$this->assertSame( 'POST', $response->getHeaderLine( 'Allow' ) );
	}

	/**
	 * Test that body param q is set
	 *
	 * @covers \MediaWiki\Extension\Math\Rest\Check::checkPreconditions
	 */
	public function testMissingParamMethod() {
		$request = new RequestData( [
			'uri' => new Uri( '/rest/math/v0/check/tex' ),
			'method' => 'POST',
			'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
			'postParams' => [
				'q' => '',
			],
		] );
		$router = $this->createRouter( $request );
		$response = $router->execute( $request );

		$this->assertSame( 400, $response->getStatusCode() );
		$this->assertSame( 'Bad Request', $response->getReasonPhrase() );
		$body = $response->getBody()->getContents();
		$data = json_decode( $body, true );
		$this->assertSame( 'q', $data['name'] );
	}

	/**
	 * Test that type must be one of the allowed types
	 *
	 * @covers \MediaWiki\Extension\Math\Rest\Check::checkPreconditions
	 */
	public function testWrongParamMethod() {
		$request = new RequestData( [
			'uri' => new Uri( '/rest/math/v0/check/tux' ),
			'method' => 'POST',
			'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
			'postParams' => [
				'q' => '',
			],
		] );
		$router = $this->createRouter( $request );
		$response = $router->execute( $request );
		$this->assertSame( 400, $response->getStatusCode() );
		$this->assertSame( 'Bad Request', $response->getReasonPhrase() );
		$body = $response->getBody()->getContents();
		$data = json_decode( $body, true );
		$this->assertSame( 'tux', $data['value'] );
	}

	/**
	 * Test that type must be one of the allowed types
	 *
	 * @covers \MediaWiki\Extension\Math\Rest\Check::checkPreconditions
	 */
	public function testGoodRequest() {
		$expectedStatus = 200;
		$expectedBody = "something";
		$testInput = 'abc';
		$testType = 'tex';
		$this->setFakeChecker( $expectedStatus, $expectedBody, $testInput, $testType );
		$request = new RequestData( [
			'uri' => new Uri( "/rest/math/v0/check/$testType" ),
			'method' => 'POST',
			'headers' => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
			'postParams' => [
				'q' => $testInput,
			],
		] );
		$router = $this->createRouter( $request );
		$response = $router->execute( $request );
		$body = $response->getBody()->getContents();
		$this->assertSame( $expectedBody, $body );
		$this->assertSame( $expectedStatus, $response->getStatusCode() );
	}

	/**
	 * @param int $expectedStatus
	 * @param string $expectedBody
	 * @param string $testInput
	 * @param string $testType
	 * @return InputCheckFactory|\PHPUnit\Framework\MockObject\MockObject
	 */
	private function setFakeChecker(
		int $expectedStatus, string $expectedBody, string $testInput, string $testType
	) {
		$fakeChecker = $this->createMock( InputCheckFactory::class );
		$fakeMathoidCheck = $this->createMock( MathoidChecker::class );
		$fakeMathoidCheck->expects( $this->once() )
			->method( 'getCheckResponse' )
			->willReturn( [ $expectedStatus, $expectedBody ] );
		$fakeChecker->expects( $this->once() )
			->method( 'getChecker' )
			->with( $testInput, $testType )
			->willReturn( $fakeMathoidCheck );
		$this->setService( 'MathCheckerFactory', $fakeChecker );
	}

}
