'use strict';
var assert = require( 'assert' ),
	EditPage = require( '../../../../core/tests/selenium/pageobjects/edit.page' ),
	MathPage = require( '../pageobjects/math.page' ),
	UserLoginPage = require( '../../../../core/tests/selenium/pageobjects/userlogin.page' );

describe( 'Math', function () {

	before( function () {
		// disable VisualEditor welcome dialog
		UserLoginPage.open();
		browser.localStorage( 'POST', { key: 've-beta-welcome-dialog', value: '1' } );
	} );

	it( 'should work for addition', function () {

		// page should have random name
		var pageName = Math.random().toString();

		// create a page with a simple addition
		EditPage.edit( pageName, '<math>3 + 2</math>' );

		// check if the page displays the image
		assert( MathPage.img.isExisting() );

	} );

} );
