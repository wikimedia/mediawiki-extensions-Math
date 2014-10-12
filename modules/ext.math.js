( function ( $ ) {
	'use strict';
	// The MW_MATH_PNG and MW_MATH_MATHML constants are taken from Math.php
	var MW_MATH_PNG = 0, MW_MATH_MATHML = 5, img, url;

	// If MathPlayer is installed we show the MathML rendering.
	if (navigator.userAgent.indexOf('MathPlayer') > -1) {
		$( '.mwe-math-mathml-a11y' ).removeClass( 'mwe-math-mathml-a11y' );
		$( '.mwe-math-fallback-image-inline, .mwe-math-fallback-image-display' ).css( 'display', 'none' );
		return;
	}

	// We verify whether SVG as <img> is supported and otherwise use the
	// PNG fallback. See https://github.com/Modernizr/Modernizr/blob/master/feature-detects/svg/asimg.js
	if (!document.implementation.hasFeature('http://www.w3.org/TR/SVG11/feature#Image', '1.1')) {
		$( '.mwe-math-fallback-image-inline, .mwe-math-fallback-image-display' ).each(function() {
			// Create a new PNG image to use as the fallback.
			img = document.createElement('img');
			url = this.style.backgroundImage.match(/url\('?([^']*)'?\)/)[1];
			img.setAttribute( 'src', url.replace('mode=' + MW_MATH_MATHML, 'mode=' + MW_MATH_PNG) );
			img.setAttribute( 'class', 'tex mwe-math-fallback-image-' + ($( this ).hasClass('mwe-math-fallback-image-inline') ? 'inline' : 'display') );
			img.setAttribute( 'aria-hidden', 'true' );
			this.parentNode.insertBefore( img, this );

			// Hide the SVG fallback.
			$( this ).css( 'display', 'none' );
		});
	}
}( jQuery ) );
