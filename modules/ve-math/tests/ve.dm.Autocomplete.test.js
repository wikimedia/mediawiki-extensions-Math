QUnit.module( 've.dm.Autocomplete (Math)', ve.test.utils.mwEnvironment );

QUnit.test( 'Autocomplete list', function ( assert ) {
	assert.notStrictEqual(
		ve.ui.MWMathDialog.static.autocompleteWordList,
		undefined,
		'autocomplete list exists' );
	assert.notEqual(
		ve.ui.MWMathDialog.static.autocompleteWordList.indexOf( '\\alpha' ),
		-1,
		'autocomplete contains \\alpha' );
} );
