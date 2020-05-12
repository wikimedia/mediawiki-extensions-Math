<?php

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;
use MediaWiki\MediaWikiServices;

return [
	'MathCheckerFactory' => function ( MediaWikiServices $services ): InputCheckFactory {
		return new InputCheckFactory(
			new ServiceOptions(
				InputCheckFactory::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			),
			$services->getMainWANObjectCache(),
			$services->getHttpRequestFactory()
		);
	},
];
