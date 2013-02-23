/**
 * From https://en.wikipedia.org/wiki/User:Nageh/mathJax.js
 */
(function ( mw, $ ) {
/**
 * renders all TeX inside the given elements, executes optional callback function after everything is rendered
 * @param callback {function} optional callback function
 */
$.fn.renderTeX = function ( callback ) {
	var elem = this.find( '.tex' ).parent() //get all elements containing an element with class tex
		.toArray();

	if ( !$.isFunction( callback ) ) {
		callback = $.noop;
	}

	function render () {
		MathJax.Hub.Queue( ['Typeset', MathJax.Hub, elem, callback] );
	}

	mw.loader.using( 'ext.math.mathjax', function () {
		if ( MathJax.isReady ) {
			render();
		} else {
			MathJax.Hub.Startup.signal.MessageHook( 'End', render );
		}
	} );

	return this;
};

if ( mw.mathJax === undefined ) {
	mw.mathJax = {};
}

mw.mathJax.config = $.extend( true, {
	root: mw.config.get( 'wgExtensionAssetsPath' ) + '/Math/modules/MathJax',
	config: ['TeX-AMS-texvc_HTML.js'],
	'v1.0-compatible': false,
	styles: {
		'.mtext': {
			'font-family': 'sans-serif ! important',
			'font-size': '80%'
		}
	},
	displayAlign: 'left',
	menuSettings: {
		zoom: 'Click'
	},
	'HTML-CSS': {
		imageFont: null,
		availableFonts: ['TeX']
	}
}, mw.mathJax.config );

mw.mathJax.Config = function () {
	MathJax.Hub.Config( mw.mathJax.config );
	MathJax.OutputJax.fontDir = mw.config.get( 'wgExtensionAssetsPath' ) + '/Math/modules/MathJax/fonts';
};

function init () {
	// create configuration element
	var	config = 'mediaWiki.mathJax.Config();',
		script = document.createElement( 'script' );
	script.setAttribute( 'type', 'text/x-mathjax-config' );
	if ( window.opera ) {
		script.innerHTML = config;
	} else {
		script.text = config;
	}
	document.getElementsByTagName( 'head' )[0].appendChild( script );

	mw.loader.load( 'ext.math.mathjax' );
}

init();

})( mediaWiki, jQuery );
