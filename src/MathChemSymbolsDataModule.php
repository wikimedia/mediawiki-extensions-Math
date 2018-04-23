<?php

/**
 * Resource loader module providing extra data from the server to Chem.
 *
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
 * @license MIT
 */
class MathChemSymbolsDataModule extends ResourceLoaderModule {

	protected $origin = self::ORIGIN_USER_SITEWIDE;
	protected $targets = [ 'desktop', 'mobile' ];

	public function getScript( ResourceLoaderContext $context ) {
		return 've.ui.MWChemDialog.static.setSymbols(' .
				file_get_contents( __DIR__ . '/../modules/ve-math/chemSymbols.json' ) .
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
