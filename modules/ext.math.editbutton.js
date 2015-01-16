( function ( mw ) {
	if ( mw.toolbar ) {
		var iconPath = mw.config.get( 'wgExtensionAssetsPath' ) + '/Math/images/';
		mw.toolbar.addButton( {
			imageFile: iconPath + 'button_math.png',
			speedTip: mw.msg( 'math_tip' ),
			tagOpen: '<math>',
			tagClose: '</math>',
			sampleText: mw.msg( 'math_sample' ),
			imageId: 'mw-editbutton-math'
		} );
	}
}( mediaWiki ) );
