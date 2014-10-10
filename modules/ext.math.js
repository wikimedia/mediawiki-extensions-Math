( function ( $ ) {
	'use strict';
	// These constants are taken from Math.php
	var MW_MATH_PNG = 0, MW_MATH_MATHML = 5;

	// If MathPlayer is installed we show the MathML rendering.
	if (navigator.userAgent.indexOf('MathPlayer') > -1) {
		$( '.mwe-math-mathml-a11y' ).removeClass( 'mwe-math-mathml-a11y' );
		$( 'img.mwe-math-fallback-svg-inline, img.mwe-math-fallback-svg-display' ).css( 'display', 'none' );
		return;
	}

	// We verify whether SVG as <img> is supported and otherwise use the
	// PNG fallback. See https://github.com/Modernizr/Modernizr/blob/master/feature-detects/svg/asimg.js
	if (!document.implementation.hasFeature('http://www.w3.org/TR/SVG11/feature#Image', '1.1')) {
		$( 'img.mwe-math-fallback-svg-inline, img.mwe-math-fallback-svg-display' ).each(function() {
			this.setAttribute('src', this.src.replace('mode=' + MW_MATH_MATHML, 'mode=' + MW_MATH_PNG));
		});
		$( 'img.mwe-math-fallback-svg-inline' ).removeClass( 'mwe-math-fallback-svg-inline' ).addClass( 'mwe-math-fallback-png-inline' );
		$( 'img.mwe-math-fallback-svg-display' ).removeClass( 'mwe-math-fallback-svg-inline' ).addClass( 'mwe-math-fallback-png-display' );
	}
}( jQuery ) );
