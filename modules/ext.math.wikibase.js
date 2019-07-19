( function () {
	'use strict';

	var serverUrl = mw.config.get( 'wgServer' ),
		scriptPath = mw.config.get( 'wgScriptPath' ),
		repoApiUrl = serverUrl + scriptPath + '/api.php',
		contLang = mw.config.get( 'wgContentLanguage' );

	// eslint-disable-next-line no-jquery/no-global-selector
	$( '.mwe-math-wikibase-entityselector-input input' ).entityselector( {
		url: repoApiUrl,
		language: contLang,
		type: 'item'
	} ).on( 'entityselectorselected', function ( event, entityId ) {
		this.value = entityId;
	} );
}() );
