'use strict';
const assert = require( 'assert' ),
	Api = require( 'wdio-mediawiki/Api' ),
	MathPage = require( '../pageobjects/math.page' );

describe( 'Math', function () {

	it( 'should work for addition', function () {

		// page should have random name
		let pageName = Math.random().toString();
		let bot;

		before( async () => {
			bot = await Api.bot();
		} );

		// create a page with a simple addition
		browser.call(  async () => {
			await Api.edit( bot, pageName, '<math>3 + 2</math>' );
		} );

		MathPage.openTitle( pageName );

		// check if the page displays the image
		assert( MathPage.img.isExisting() );

	} );

} );
