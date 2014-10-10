( function ( $ ) {
	'use strict';
	// If MathPlayer is installed we show the MathML rendering.
	if (navigator.userAgent.indexOf('MathPlayer') > -1) {
		$( '.mwe-math-mathml-a11y' ).removeClass( 'mwe-math-mathml-a11y' );
		$( 'img' ).removeClass( 'mwe-math-fallback-svg-inline mwe-math-fallback-svg-display' );
	}
	// FIXME: for browsers without SVG support, the <img> fallback should be
	// updated to point to PNG images.
}( jQuery ) );
