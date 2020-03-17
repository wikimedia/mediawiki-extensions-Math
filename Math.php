<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'Math' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['Math'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['Math'] = __DIR__ . '/Math.alias.php';
	$wgExtensionMessagesFiles['MathAliasNoTranslate'] = __DIR__ . '/Math.alias.noTranslate.php';
	wfWarn(
		'Deprecated PHP entry point used for Math extension. Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the Math extension requires MediaWiki 1.32+' );
}
