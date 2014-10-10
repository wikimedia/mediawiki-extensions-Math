( function ( $ ) {
	'use strict';
	// If MathPlayer is installed we show the MathML rendering.
	if (navigator.userAgent.indexOf('MathPlayer') > -1) {
		$( '.mwe-math-mathml-a11y' ).removeClass( 'mwe-math-mathml-a11y' );
		$( '.mwe-math-fallback-img-inline, .mwe-math-fallback-img-display' ).css( 'display', 'none' );
		return;
	}
	// We detect whether SVG as <img> is supported and otherwise use the
	// PNG fallback. See https://github.com/Modernizr/Modernizr/blob/master/feature-detects/svg/asimg.js
	if (!document.implementation.hasFeature('http://www.w3.org/TR/SVG11/feature#Image', '1.1')) {
		$( '.mwe-math-fallback-img-inline, .mwe-math-fallback-img-display' ).each(function() {
			// TODO: update the image URI
		});
        }
}( jQuery ) );
