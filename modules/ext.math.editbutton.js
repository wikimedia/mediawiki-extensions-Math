( function ( mw, $ ) {
	if (mw.toolbar) {
		mw.toolbar.addButton( {
			imageFile: '//upload.wikimedia.org/wikipedia/en/3/34/Button_hide_comment.png',
			speedTip: 'Comment visible only for editors',
			tagOpen: '<math>',
			tagClose: '</math>',
			sampleText: 'E = m c^2',
			imageId: 'button-math'
	} );
}
}( mediaWiki, jQuery ) );