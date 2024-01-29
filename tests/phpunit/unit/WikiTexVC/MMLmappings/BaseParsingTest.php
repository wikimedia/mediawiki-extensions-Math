<?php

namespace MediaWiki\Extension\Math\Tests\WikiTexVC\MMLmappings;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing
 */
class BaseParsingTest extends TestCase {

	public function testAccent() {
		$node = new Fun1(
			'\\widetilde',
				( new Literal( 'a' ) )
			);
		$result = BaseParsing::accent( $node, [], null, 'widetilde', '007E' );
		$this->assertStringContainsString( '~', $result );
		$this->assertStringContainsString( 'mover', $result );
	}

	public function testAccentArgPassing() {
		$node = new Fun1(
			'\\widetilde',
			( new Literal( 'a' ) )
		);
		$result = BaseParsing::accent( $node, [ 'k' => 'v' ], null, 'widetilde', '007E' );
		$this->assertStringContainsString( '<mi k="v"', $result );
	}
}
