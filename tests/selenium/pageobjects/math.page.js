'use strict';
const Page = require( '../../../../../tests/selenium/pageobjects/page' );

class MathPage extends Page {

	get img() { return browser.element( '.mwe-math-fallback-image-inline' ); }

}
module.exports = new MathPage();
