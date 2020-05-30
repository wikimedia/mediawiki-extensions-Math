<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @ingroup Maintenance
 */

require_once __DIR__ . '/../../../maintenance/Maintenance.php';
use MediaWiki\MediaWikiServices;

class MathGenerateTests extends Maintenance {
	private const REFERENCE_PAGE = 'mediawikiwiki:Extension:Math/CoverageTest';

	public function __construct() {
		parent::__construct();
		$this->requireExtension( 'Math' );
		$this->addDescription( 'Rebuilds the MathCoverage tests' );
		$this->addArg( 'page', "The page used for the testset generation.", false );
		$this->addOption(
			'offset', "If set the first n equations on the page are skipped", false, true, "o"
		);
		$this->addOption( 'length', "If set the only n equations were processed", false, true, "l" );
		$this->addOption( 'user', "User with rights to view the page", false, true, "u" );
	}

	private static function getMathTagsFromPage( $titleString ) {
		global $wgEnableScaryTranscluding;
		$title = Title::newFromText( $titleString );
		if ( $title->exists() ) {
			$article = new Article( $title );
			$wikiText = $article->getPage()->getContent()->getNativeData();
		} else {
			if ( $title->getPrefixedDBkey() === self::REFERENCE_PAGE ) {
				$wgEnableScaryTranscluding = true;
				$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
				$wikiText = $parser->interwikiTransclude( $title, 'raw' );
			} else {
				return 'Page does not exist';
			}
		}

		$wikiText = Sanitizer::removeHTMLcomments( $wikiText );
		$wikiText = preg_replace( '#<nowiki>(.*)</nowiki>#', '', $wikiText );
		$math = [];
		Parser::extractTagsAndParams( [ 'math' ], $wikiText, $math );
		return $math;
	}

	public function execute() {
		global $wgUser;
		$parserTests = [];
		$page = $this->getArg( 0, self::REFERENCE_PAGE );
		$offset = $this->getOption( 'offset', 0 );
		$length = $this->getOption( 'length', PHP_INT_MAX );
		$userName = $this->getOption( 'user', 'Maintenance script' );
		$wgUser = User::newFromName( $userName );
		$allEquations = self::getMathTagsFromPage( $page );
		if ( !is_array( $allEquations ) ) {
			echo "Could not get equations from page '$page'\n";
			echo $allEquations . PHP_EOL;
			return;
		} else {
			echo 'got ' . count( $allEquations ) . " math tags. Start processing.";
		}
		$i = 0;
		foreach ( array_slice( $allEquations, $offset, $length, true ) as $input ) {
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$output = MathRenderer::renderMath( $input[1], $input[2], 'png' );
			$output = preg_replace( '#src="(.*?)/(([a-f]|\d)*)"#', 'src="\2"', $output );
			$parserTests[] = [
				'input' => (string)$input[1],
				'params' => $input[2],
				'output' => $output
			];
			$i++;
			echo '.';
		}
		echo "Generated $i tests\n";
		file_put_contents(
			__DIR__ . '/../tests/ParserTest.json', json_encode( $parserTests, JSON_PRETTY_PRINT )
		);
	}
}

$maintClass = MathGenerateTests::class;
require_once RUN_MAINTENANCE_IF_MAIN;
