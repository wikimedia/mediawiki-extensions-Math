if (navigator.userAgent.indexOf("Firefox") == -1){
	mw.loader.using('ext.math.mathjax.enabler', function () {
		mathJax.config = $.extend( true, mathJax.config, {config: ['MML_HTMLorMML-full.js']} );
		MathJax.Hub.Configured();
	});
}
