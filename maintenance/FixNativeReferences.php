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

use MediaWiki\Extension\Math\MathReferenceData;
use MediaWiki\Json\FormatJson;
use MediaWiki\Maintenance\Maintenance;

// @codeCoverageIgnoreStart
require_once __DIR__ . '/../../../maintenance/Maintenance.php';
// @codeCoverageIgnoreEnd

/**
 * Regenerates the native MathML regression references.
 */
class FixNativeReferences extends Maintenance {

	private const REFERENCE_PATH = __DIR__ . '/../tests/phpunit/integration/WikiTexVC/data/reference.json';

	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Update reference rendering for regression tests.
		 Changes should be investigated manually.' );
		$this->addOption( 'skip-svg', 'Skip regenerating client-side SVG snapshots '
			. '(runs npm run qunit in a real browser; slow, requires Firefox).' );
	}

	public function execute() {
		$file = file_get_contents( self::REFERENCE_PATH );
		$json = json_decode( $file, true );
		if ( array_is_list( $json ) ) {
			$json = MathReferenceData::groupCases( $json );
		}
		$success = true;
		$allEntries = [];
		foreach ( $json as $hash => $entry ) {
			$success = MathReferenceData::renderReferenceEntry( $entry ) && $success;
			$allEntries[$hash] = $entry;
		}

		if ( $this->hasOption( 'skip-svg' ) ) {
			$this->output( "Skipping SVG reference update (--skip-svg).\n" );
		} else {
			$this->updateSvgReferences( $allEntries );
		}

		file_put_contents( self::REFERENCE_PATH, FormatJson::encode( $allEntries, "\t", FormatJson::ALL_OK )
			. "\n" );
		if ( !$success ) {
			$this->fatalError( "Some entries were skipped. Please investigate.\n" );
		}
		$this->output( "Regression.json successfully updated.\n" );
	}

	/**
	 * Computes client-side SVG via a real browser (npm run qunit) and merges
	 * it into $allEntries. Needs a real DOM: ext.math.mathjax.js's mmlFilters
	 * won't run otherwise.
	 *
	 * @param array<string,array> &$allEntries
	 */
	private function updateSvgReferences( array &$allEntries ): void {
		// runs `npm run qunit`
		$descriptors = [ 1 => [ 'pipe', 'w' ], 2 => [ 'pipe', 'w' ] ];
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.proc_open
		$process = proc_open( [ 'npm', 'run', 'qunit' ], $descriptors, $pipes, __DIR__ . '/..' );
		// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.is_resource
		if ( !is_resource( $process ) ) {
			$this->output( "Could not start npm; skipping SVG reference update.\n" );
			return;
		}
		$stdout = stream_get_contents( $pipes[1] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		$exitCode = proc_close( $process );

		if ( !str_contains( $stdout, 'tests completed' ) ) {
			$this->output( "npm run qunit exited with code $exitCode without completing; " .
				"skipping SVG reference update.\n" );
			return;
		}
		// Browser JS can't write files, and a structured Karma channel needs a
		// custom reporter (registered in core's Gruntfile.js, out of scope here) --
		// scraping the console.log line Karma already prints is the only channel
		// available without a new dependency or a core change.
		$updated = 0;
		foreach ( explode( "\n", $stdout ) as $line ) {
			if ( !preg_match( "/^LOG: 'SVG_REFERENCE_DATA:(.*)'\$/", $line, $matches ) ) {
				continue;
			}
			$data = json_decode( $matches[1], true );
			if ( !is_array( $data ) || !isset( $data['hash'] ) || !isset( $data['svg'] ) ) {
				continue;
			}
			if ( ( $data['index'] ?? null ) === null ) {
				$allEntries[$data['hash']]['svg'] = $data['svg'];
			} else {
				$allEntries[$data['hash']]['outputs'][$data['index']]['svg'] = $data['svg'];
			}
			$updated++;
		}
		$this->output( $updated === 0
			? "No SVG reference snapshots needed updating.\n"
			: "Updated $updated SVG reference snapshots.\n" );
	}

}

// @codeCoverageIgnoreStart
$maintClass = FixNativeReferences::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
