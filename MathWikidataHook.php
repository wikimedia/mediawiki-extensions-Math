<?php

use ValueFormatters\FormatterOptions;
use ValueParsers\StringParser;
use Wikibase\Rdf\DedupeBag;
use Wikibase\Rdf\EntityMentionListener;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Repo\Parsers\WikibaseStringValueNormalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikimedia\Purtle\RdfWriter;

class MathWikidataHook {

	/**
	 * Add Datatype "Math" to the Wikibase Repository
	 */
	public static function onWikibaseRepoDataTypes( array &$dataTypeDefinitions ) {
		global $wgMathEnableWikibaseDataType;

		if ( !$wgMathEnableWikibaseDataType ) {
			return;
		}

		$dataTypeDefinitions['PT:math'] = [
			'value-type'                 => 'string',
			'validator-factory-callback' => function() {
				// load validator builders
				$factory = WikibaseRepo::getDefaultValidatorBuilders();

				// initialize an array with string validators
				// returns an array of validators
				// that add basic string validation such as preventing empty strings
				$validators = $factory->buildStringValidators();
				$validators[] = new MathValidator();
				return $validators;
			},
			'parser-factory-callback' => function( ParserOptions $options ) {
				$repo = WikibaseRepo::getDefaultInstance();
				$normalizer = new WikibaseStringValueNormalizer( $repo->getStringNormalizer() );
				return new StringParser( $normalizer );
			},
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				global $wgOut;
				$styles = [ 'ext.math.desktop.styles', 'ext.math.scripts', 'ext.math.styles' ];
				$wgOut->addModuleStyles( $styles );
				return new MathFormatter( $format );
			},
			'rdf-builder-factory-callback' => function (
				$mode,
				RdfVocabulary $vocab,
				RdfWriter $writer,
				EntityMentionListener $tracker,
				DedupeBag $dedupe
			) {
				return new MathMLRdfBuilder();
			},
		];
	}

	/*
	 * Add Datatype "Math" to the Wikibase Client
	 */
	public static function onWikibaseClientDataTypes( array &$dataTypeDefinitions ) {
		global $wgMathEnableWikibaseDataType;

		if ( !$wgMathEnableWikibaseDataType ) {
			return;
		}

		$dataTypeDefinitions['PT:math'] = [
			'value-type'                 => 'string',
			'formatter-factory-callback' => function( $format, FormatterOptions $options ) {
				global $wgOut;
				$styles = [ 'ext.math.desktop.styles', 'ext.math.scripts', 'ext.math.styles' ];
				$wgOut->addModuleStyles( $styles );
				return new MathFormatter( $format );
			},
		];
	}

}
