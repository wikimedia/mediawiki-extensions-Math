'use strict';
var assert = require( 'assert' ),
	EditPage = require( '../../../../../tests/selenium/pageobjects/edit.page' ),
	MathPage = require( '../pageobjects/math.page' );

describe( 'Math', function () {

	it( 'should work for addition', function () {

		// page should have random name
		var pageName = Math.random().toString();

		// create a page with a simple addition
		EditPage.edit( pageName, '<math>3 + 2</math>' );

		// check if the page displays the image
		assert( MathPage.img.isExisting() );

	} );

} );
