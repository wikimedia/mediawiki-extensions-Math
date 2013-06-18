//TODO: Find a better way to determine if the browser can handle mathml.
if (navigator.userAgent.indexOf('Firefox') === -1){
	mw.loader.using( 'ext.math.mathjax.enabler.base', function () {
		MathJaxEnabler('MML_HTMLorMML-full.js');
		} );
}
