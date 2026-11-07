<?php
namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods
 */
class BaseMethodsTest extends MediaWikiIntegrationTestCase {
	public function testCheckAndParseOperatorWithU() {
		$bm = new BaseMethods();
		$this->assertTrue( $bm->checkAndParseOperator( '\u3009', null, [], [] )->isEmpty() );
		$this->assertTrue( $bm->checkAndParseOperator( '&#x3009;', null, [], [] )->isEmpty() );
	}

	public function testCheckAndParseOperatorNormal() {
		$bm = new BaseMethods();
		$op = $bm->checkAndParseOperator( '!', null, [], [] );
		$this->assertStringContainsString( '!</mo>', $op );
	}

	public function testCheckAndParseOperatorSpecial() {
		$bm = new BaseMethods();
		$op = $bm->checkAndParseOperator( '>', null, [], [] );
		$this->assertStringContainsString( '&gt;</mo>', $op );
	}
}
