//TODO: Find a better way to determine if the browser can handle mathml.
if (navigator.userAgent.indexOf('Firefox') === -1){
	mw.loader.using( 'ext.math.mathjax.enabler.base', function () {
		 var instance = new MathJaxEnabler('MML_HTMLorMML-full.js');
		} );
}
