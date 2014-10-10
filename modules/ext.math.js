( function ( $ ) {
	'use strict';
	// If MathPlayer is installed we show the MathML rendering.
	if (navigator.userAgent.indexOf('MathPlayer') > -1) {
		$( 'span.mwe-math-mathml-a11y' ).removeClass( 'mwe-math-mathml-a11y' );
	}
	// FIXME: for browsers without SVG support, the <img> fallback should be
	// updated to point to PNG images.
}( jQuery ) );
