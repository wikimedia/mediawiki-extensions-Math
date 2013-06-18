if (navigator.userAgent.indexOf("Firefox") == -1){
	mw.loader.load('ext.math.mathjax.enabler');
	mathJax.config = $.extend( true, {config: ['MML_HTMLorMML-full.js']}, mathJax.config );
}
