<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\Extension\Math\Render\RendererFactory;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

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
	'Math.RendererFactory' => static function ( MediaWikiServices $services ): RendererFactory {
		return new RendererFactory(
			new ServiceOptions(
				RendererFactory::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getUserOptionsLookup(),
			LoggerFactory::getInstance( 'Math' )
		);
	},
];
