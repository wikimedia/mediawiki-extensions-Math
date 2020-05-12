<?php

namespace MediaWiki\Extension\Math\InputCheck;

use HashBagOStuff;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use WANObjectCache;

class MathoidCheckerTest extends MediaWikiTestCase {
	private const SAMPLE_KEY =
		'global:MediaWiki\Extension\Math\InputCheck\MathoidChecker:1.0.0:tex:\sin_x';

	/**
	 * @covers \MediaWiki\Extension\Math\InputCheck\MathoidChecker::getCacheKey
	 */
	public function testCacheKey() {
		$checker = $this->getMathoidChecker();
		$realKey = $checker->getCacheKey();
		$this->assertSame( self::SAMPLE_KEY, $realKey );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\InputCheck\MathoidChecker::getCheckResponse
	 */
	public function testResponseFromCache() {
		$fakeWAN = new WANObjectCache( [ 'cache' => new HashBagOStuff() ] );
		$fakeWAN->set( self::SAMPLE_KEY, 'expected' );
		// double check that the fake works
		$this->assertSame( 'expected', $fakeWAN->get( self::SAMPLE_KEY ) );
		$this->setService( 'MainWANObjectCache', $fakeWAN );
		$checker = $this->getMathoidChecker();
		$this->assertSame( 'expected', $checker->getCheckResponse() );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\InputCheck\MathoidChecker::getCheckResponse
	 */
	public function testResponseFromResponse() {
		$fakeWAN = WANObjectCache::newEmpty();
		$fakeWAN->set( self::SAMPLE_KEY, 'expected' );
		// double check that the fake does not works
		$this->assertSame( false, $fakeWAN->get( self::SAMPLE_KEY ) );
		$this->setService( 'MainWANObjectCache', $fakeWAN );
		$this->setFakeRequest( 200, 'expected' );
		$checker = $this->getMathoidChecker();
		$this->assertSame( [ 200, 'expected' ], $checker->getCheckResponse() );
	}

	/**
	 * @covers \MediaWiki\Extension\Math\InputCheck\MathoidChecker::getCheckResponse
	 */
	public function testFailedResponse() {
		$fakeWAN = WANObjectCache::newEmpty();
		$fakeWAN->set( self::SAMPLE_KEY, 'expected' );
		// double check that the fake does not works
		$this->assertSame( false, $fakeWAN->get( self::SAMPLE_KEY ) );
		$this->setService( 'MainWANObjectCache', $fakeWAN );
		$this->setFakeRequest( 401, false );
		$checker = $this->getMathoidChecker();
		$this->expectException( 'MWException' );
		$checker->getCheckResponse();
	}

	/**
	 * @return MathoidChecker
	 */
	private function getMathoidChecker(): MathoidChecker {
		return MediaWikiServices::getInstance()->getService( 'Math.CheckerFactory' )
			->newMathoidChecker( '\sin x', 'tex' );
	}

	private function setFakeRequest( $returnStatus, $content ): void {
		$fakeHTTP = $this->createMock( HttpRequestFactory::class );
		$fakeRequest = $this->createMock( \MWHttpRequest::class );
		$fakeRequest->expects( $this->once() )->method( 'execute' )->willReturn( true );
		$fakeRequest->expects( $this->once() )->method( 'getStatus' )->willReturn( $returnStatus );
		if ( $content ) {
			$fakeRequest->expects( $this->once() )->method( 'getContent' )->willReturn( $content );
		} else {
			$fakeRequest->expects( $this->never() )->method( 'getContent' );
		}
		$fakeHTTP->expects( $this->once() )->method( 'create' )->willReturn( $fakeRequest );
		$this->setService( 'HttpRequestFactory', $fakeHTTP );
	}

}
