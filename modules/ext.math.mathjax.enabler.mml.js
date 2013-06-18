( function ( mw ) {
//	TODO: Find a better way to determine if the browser can handle mathml.
	if (navigator.userAgent.indexOf('Firefox') === -1){

		mw.libs.MathJaxEnabler('MML_HTMLorMML-full.js');

	}
}( mediaWiki ) );