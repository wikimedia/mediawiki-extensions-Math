<?php

use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Extension\Math\InputCheck\LocalChecker;
use MediaWiki\Message\Message;
use MediaWiki\Tests\Unit\MockServiceDependenciesTrait;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * @method InputCheckFactory newServiceInstance(string $serviceClass, array $parameterOverrides)
 * @covers \MediaWiki\Extension\Math\InputCheck\InputCheckFactory
 */
class InputCheckFactoryTest extends MediaWikiIntegrationTestCase {

	use MockServiceDependenciesTrait;

	/** @var WANObjectCache */
	private $fakeWAN;

	protected function setUp(): void {
		parent::setUp();
		$this->fakeWAN = WANObjectCache::newEmpty();
	}

	public function testNewLocalChecker() {
		$checker = $this->newServiceInstance( InputCheckFactory::class, [] )
			->newLocalChecker( 'FORMULA', 'tex' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
	}

	public function testInvalidLocalChecker() {
		$myFactory = new InputCheckFactory(
			$this->fakeWAN
		);
		$checker = $myFactory->newLocalChecker( 'FORMULA', 'INVALIDTYPE' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
		$this->assertInstanceOf( Message::class, $checker->getError() );
		$this->assertFalse( $checker->isValid() );
	}

	public function testNewLocalCheckerWired() {
		$checker = $this->newServiceInstance( InputCheckFactory::class, [] )
			->newLocalChecker( 'FORMULA', 'TYPE' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
	}

	public function testNewLocalCheckerExplicit() {
		$myFactory = new InputCheckFactory(
			$this->fakeWAN
		);

		$checker = $myFactory->newLocalChecker( 'FORMULA', 'tex' );
		$this->assertInstanceOf( LocalChecker::class, $checker );
	}
}
