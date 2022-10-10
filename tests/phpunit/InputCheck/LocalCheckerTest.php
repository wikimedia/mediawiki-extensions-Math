<?php

namespace MediaWiki\Extension\Math\InputCheck;

use InvalidArgumentException;
use MediaWikiIntegrationTestCase;
use Message;
use MockHttpTrait;

/**
 * @group Math
 * @license GPL-2.0-or-later
 * tbd move this to unittests
 * @covers \MediaWiki\Extension\Math\InputCheck\LocalChecker
 */
class LocalCheckerTest extends MediaWikiIntegrationTestCase {
	use MockHttpTrait;

	public function testValid() {
		$checker = new LocalChecker( '\sin x^2' );
		$this->assertNull( $checker->getError() );
		$this->assertTrue( $checker->isValid() );
		$this->assertNull( $checker->getError() );
		$this->assertSame( '\\sin x^{2}', $checker->getValidTex() );
	}

	public function testValidType() {
		$checker = new LocalChecker( '\sin x^2', 'tex' );
		$this->assertTrue( $checker->isValid() );
	}

	public function testInvalidType() {
		$this->expectException( InvalidArgumentException::class );
		new LocalChecker( '\sin x^2', 'chem' );
	}

	public function testInvalid() {
		$checker = new LocalChecker( '\sin\newcommand' );
		$this->assertFalse( $checker->isValid() );

		$this->assertStringContainsString(
			Message::newFromKey( 'math_unknown_function', '\newcommand' )
				->inContentLanguage()
				->escaped(),
			$checker->getError()
				->inContentLanguage()
				->escaped()
		);

		$this->assertNull( $checker->getValidTex() );
	}

	public function testErrorSyntax() {
		$checker = new LocalChecker( '\left(' );
		$this->assertFalse( $checker->isValid() );
		$this->assertStringContainsString(
			Message::newFromKey( 'math_syntax_error' )
				->inContentLanguage()
				->escaped(),
			$checker->getError()
				->inContentLanguage()
				->escaped()
		);
	}
}
