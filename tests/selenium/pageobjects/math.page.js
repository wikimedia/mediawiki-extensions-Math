'use strict';
const Page = require( 'wdio-mediawiki/Page' );

class MathPage extends Page {

	get img() { return browser.element( '.mwe-math-fallback-image-inline' ); }

}
module.exports = new MathPage();
