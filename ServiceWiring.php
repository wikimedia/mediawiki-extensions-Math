<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Extension\Math\Math;
use MediaWiki\Extension\Math\MathConfig;
use MediaWiki\Extension\Math\MathWikibaseConfig;
use MediaWiki\Extension\Math\MathWikibaseConnector;
use MediaWiki\Extension\Math\Render\RendererFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\Client\WikibaseClient;

return [
	'Math.CheckerFactory' => static function ( MediaWikiServices $services ): InputCheckFactory {
		return new InputCheckFactory(
			new ServiceOptions(
				InputCheckFactory::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getMainWANObjectCache(),
			$services->getHttpRequestFactory(),
			LoggerFactory::getInstance( 'Math' )
		);
	},
	'Math.Config' => static function ( MediaWikiServices $services ): MathConfig {
		return new MathConfig(
			new ServiceOptions( MathConfig::CONSTRUCTOR_OPTIONS, $services->getMainConfig() )
		);
	},
	'Math.RendererFactory' => static function ( MediaWikiServices $services ): RendererFactory {
		return new RendererFactory(
			new ServiceOptions(
				RendererFactory::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			Math::getMathConfig( $services ),
			$services->getUserOptionsLookup(),
			LoggerFactory::getInstance( 'Math' )
		);
	},
	'Math.WikibaseConnector' => static function ( MediaWikiServices $services ): MathWikibaseConnector {
		return new MathWikibaseConnector(
			$services->get( 'Math.WikibaseConfig' ),
			WikibaseClient::getRepoLinker( $services ),
			$services->getLanguageFactory(),
			LoggerFactory::getInstance( 'Math' )
		);
	},
	'Math.WikibaseConfig' => static function ( MediaWikiServices $services ): MathWikibaseConfig {
		return new MathWikibaseConfig(
			WikibaseClient::getEntityIdParser( $services ),
			WikibaseClient::getEntityRevisionLookup( $services ),
			WikibaseClient::getFallbackLabelDescriptionLookupFactory( $services ),
			WikibaseClient::getSite( $services ),
			$services->getMainConfig()
		);
	},
];
