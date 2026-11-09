<?php

namespace MediaWiki\Extension\Math\Tests;

use MediaWiki\Extension\Math\HookHandlers\ParserHooksHandler;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Math\HookHandlers\ParserHooksHandler
 * @group Database
 */
class MathParsoidIntegrationTest extends MediaWikiIntegrationTestCase {

	private function parse( string $wikitext ): string {
		$options = ParserOptions::newFromAnon();
		$options->setOption( 'math', 'native' );
		$options->setUseParsoid();
		return $this->getServiceContainer()->getParsoidParserFactory()->create()
			->parse( $wikitext, Title::newFromText( 'Test' ), $options )->getContentHolderText();
	}

	public function testParsoidCallsSourceToDom() {
		$siteConfig = $this->getServiceContainer()->getParsoidSiteConfig();
		foreach ( [ 'math', 'chem', 'ce' ] as $tag ) {
			$this->assertInstanceOf( ParserHooksHandler::class, $siteConfig->getExtTagImpl( $tag ), $tag );
		}
	}

	public static function provideTags(): array {
		// H2O as TeX has an italic H, as chemistry an upright one
		$italic = '/<mi id="\w+">H<\/mi>/';
		$upright = '/<mi mathvariant="normal" id="\w+">H<\/mi>/';
		return [
			'math' => [ 'math', 'math', $italic ],
			'MATH' => [ 'MATH', 'math', $italic ],
			'chem' => [ 'chem', 'chem', $upright ],
			'CHEM' => [ 'CHEM', 'chem', $upright ],
			'ce' => [ 'ce', 'ce', $upright ],
		];
	}

	/** @dataProvider provideTags */
	public function testTags( string $tag, string $name, string $h ) {
		$html = $this->parse( "<$tag>H2O</$tag>" );
		$this->assertStringContainsString( "typeof=\"mw:Extension/$name\"", $html );
		$this->assertMatchesRegularExpression( $h, $html );
	}

	public function testSyntaxError() {
		$this->assertStringContainsString( 'class="error texerror"', $this->parse( '<math>\notacommand</math>' ) );
	}

	public function testMathInRef() {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'Cite' ) ) {
			$this->markTestSkipped( 'Cite is not loaded' );
		}
		$html = $this->parse( "<math>a</math><ref>n <math>b</math></ref>\n<references />" );
		$this->assertSame( 2, substr_count( $html, '<math ' ), $html );
	}
}
