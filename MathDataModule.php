<?php
/**
 * Resource loader module providing extra data from the server to Math.
 *
 * @file
 * @ingroup Extensions
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

class MathDataModule extends ResourceLoaderModule {

	/* Protected Members */

	protected $origin = self::ORIGIN_USER_SITEWIDE;
	protected $targets = [ 'desktop', 'mobile' ];

	/* Methods */

	public function getScript( ResourceLoaderContext $context ) {
		return
			've.ui.MWMathDialog.static.setSymbols(' .
				file_get_contents( __DIR__ . '/modules/ve-math/symbols.json' ) .
			');';
	}

	public function getDependencies( ResourceLoaderContext $context = null ) {
		return [
			'ext.math.visualEditor',
		];
	}

	public function enableModuleContentVersion() {
		return true;
	}
}
