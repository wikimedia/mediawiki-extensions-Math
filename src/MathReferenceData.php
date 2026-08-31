<?php
/**
 * MediaWiki math extension
 *
 * @copyright 2002-2026 various MediaWiki contributors
 * @license GPL-2.0-or-later
 */

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;
use MediaWiki\Config\Config;
use MediaWiki\Extension\Math\InputCheck\LocalChecker;
use MediaWiki\HookContainer\HookContainer;
use MediaWiki\MediaWikiServices;
use Wikimedia\ObjectCache\WANObjectCache;

/**
 * Static utility class for grouping and rendering MathML regression references.
 */
final class MathReferenceData {
	private const RNG_PATH = __DIR__ . '/WikiTexVC/mathml4-core.rng';

	/** MD5 is used for compatibility with https://zenodo.org/records/15162182. */
	public const HASH_ALGORITHM = 'md5';

	/** @codeCoverageIgnore */
	private function __construct() {
		// This is a static utility class.
	}

	/**
	 * Converts flat references to the hash-keyed format when new entries are added.
	 * Cases with the same input are grouped as output variants.
	 *
	 * @param array[] $cases
	 * @return array<string,array>
	 */
	public static function groupCases( array $cases ): array {
		$references = [];
		foreach ( $cases as $case ) {
			$input = $case['input'] ?? null;
			if ( !is_string( $input ) ) {
				throw new InvalidArgumentException( 'Every reference case must have a string input.' );
			}

			$hash = hash( self::HASH_ALGORITHM, $input );
			$variant = $case;
			unset( $variant['input'] );

			if ( !isset( $references[$hash] ) ) {
				$references[$hash] = array_key_exists( 'params', $variant ) ?
					[ 'input' => $input, 'outputs' => [ $variant ] ] : $case;
				continue;
			}

			if ( $references[$hash]['input'] !== $input ) {
				throw new InvalidArgumentException(
					"Hash collision for $hash between inputs [{$references[$hash]['input']}] and [$input]."
				);
			}

			if ( !isset( $references[$hash]['outputs'] ) ) {
				$existing = $references[$hash];
				unset( $existing['input'] );
				if ( $existing === $variant ) {
					continue;
				}
				$references[$hash] = [ 'input' => $input, 'outputs' => [ $existing ] ];
			}

			self::addVariant( $references[$hash]['outputs'], $variant, $input );
		}
		ksort( $references );
		return $references;
	}

	/**
	 * Renders and validates every output of one grouped reference in place.
	 */
	public static function renderReferenceEntry(
		array &$entry,
		?MathConfig $mathConfig = null,
		?HookContainer $hookContainer = null,
		?Config $config = null
	): bool {
		$input = $entry['input'] ?? null;
		if ( !is_string( $input ) ) {
			throw new InvalidArgumentException( 'Every reference case must have a string input.' );
		}
		if ( !array_key_exists( 'outputs', $entry ) ) {
			return self::renderOutput( $entry, $mathConfig, $hookContainer, $config );
		}
		if ( !is_array( $entry['outputs'] ) || $entry['outputs'] === [] ) {
			throw new InvalidArgumentException( 'A grouped reference must have at least one output.' );
		}

		$success = true;
		foreach ( $entry['outputs'] as &$variant ) {
			if ( !is_array( $variant ) ) {
				throw new InvalidArgumentException( 'A grouped reference contains an invalid output.' );
			}
			$variant['input'] = $input;
			$success = self::renderOutput(
				$variant, $mathConfig, $hookContainer, $config
			) && $success;
			unset( $variant['input'] );
		}
		unset( $variant );
		return $success;
	}

	/**
	 * Renders and validates one output variant in place.
	 */
	private static function renderOutput(
		array &$entry,
		?MathConfig $mathConfig,
		?HookContainer $hookContainer,
		?Config $config
	): bool {
		$mathConfig ??= Math::getMathConfig();
		$hookContainer ??= MediaWikiServices::getInstance()->getHookContainer();
		$config ??= MediaWikiServices::getInstance()->getMainConfig();
		$renderer = new MathNativeMML(
			$entry['input'], $entry['params'] ?? [], WANObjectCache::newEmpty(), $mathConfig
		);
		$renderer->setRawError( true );
		$renderer->setReferenceServices(
			$hookContainer,
			$config,
			new LocalChecker( WANObjectCache::newEmpty(), $renderer->getTex(), 'tex' )
		);
		// T434686: Fake mathjax mode to avoid adding class=mathjax_ignore to the output.
		$renderer->setMode( MathConfig::MODE_NATIVE_JAX );
		$result = $renderer->render();
		$entry['output'] = $renderer->getMathml();
		if ( !$result ) {
			$entry['skipped'] = true;
			$entry['error'] = $renderer->getLastError();
		}
		$validation = self::validateSchema( $renderer->getMathml(), self::RNG_PATH );
		if ( $validation ) {
			$entry['core-validation'] = $validation;
		} else {
			unset( $entry['core-validation'] );
		}
		return $result;
	}

	/**
	 * Adds a distinct parameter variant and rejects ambiguous outputs.
	 *
	 * @param array[] &$variants
	 * @param array $variant
	 */
	private static function addVariant( array &$variants, array $variant, string $input ): void {
		foreach ( $variants as $existing ) {
			if ( $existing === $variant ) {
				return;
			}
			if ( self::getParamsKey( $existing ) === self::getParamsKey( $variant ) ) {
				throw new InvalidArgumentException( "Multiple outputs for the same parameters and input $input." );
			}
		}
		$variants[] = $variant;
	}

	/**
	 * Returns a canonical key for a variant's rendering parameters.
	 */
	private static function getParamsKey( array $variant ): string {
		$params = $variant['params'] ?? [];
		if ( !is_array( $params ) ) {
			throw new InvalidArgumentException( 'Reference params must be an array.' );
		}
		ksort( $params );
		return serialize( $params );
	}

	/**
	 * Validates MathML against the reference Relax NG schema.
	 *
	 * @return array<string,int>
	 */
	private static function validateSchema( string $mathml, string $relaxNGFile ): array {
		$doc = new DOMDocument();
		$doc->loadXML( $mathml );
		self::removeHtmlAttributes( $doc );

		libxml_use_internal_errors( true );
		libxml_clear_errors();
		$doc->relaxNGValidate( $relaxNGFile );

		return array_count_values( array_map( static fn ( $error ) => $error->message, libxml_get_errors() ) );
	}

	/**
	 * Removes HTML-only attributes before MathML schema validation.
	 */
	private static function removeHtmlAttributes( DOMNode $node ): void {
		$htmlAttributes = [ 'style', 'class' ];
		if ( $node instanceof DOMElement && $node->hasAttributes() ) {
			foreach ( $node->attributes as $attr ) {
				if ( str_starts_with( $attr->name, 'data' ) || in_array( $attr->name, $htmlAttributes ) ) {
					$node->removeAttribute( $attr->name );
				}
			}
		}
		foreach ( $node->childNodes as $child ) {
			self::removeHtmlAttributes( $child );
		}
	}
}
