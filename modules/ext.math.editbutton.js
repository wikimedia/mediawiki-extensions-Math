( function ( mw ) {
	if (mw.toolbar) {
		mw.toolbar.addButton( {
			imageFile: '//upload.wikimedia.org/wikipedia/en/3/34/Button_hide_comment.png',//window.wgContentLanguage.getImageFile( 'button-math' ),
			speedTip: mw.message( 'math_tip' ).text(),
			tagOpen: '<math>',
			tagClose: '</math>',
			sampleText: mw.message( 'math_sample' ).text(),
			imageId: 'button-math',
	} );
}
}( mediaWiki, jQuery ) );