( function ( $ ) {
	'use strict';
	// These constants are taken from Math.php
	var MW_MATH_PNG = 0, MW_MATH_MATHML = 5, img, url;

	// If MathPlayer is installed we show the MathML rendering.
	if (navigator.userAgent.indexOf('MathPlayer') > -1) {
		$( '.mwe-math-mathml-a11y' ).removeClass( 'mwe-math-mathml-a11y' );
		$( '.mwe-math-fallback-svg-inline, .mwe-math-fallback-svg-display' ).css( 'display', 'none' );
		return;
	}

	// We verify whether SVG as <img> is supported and otherwise use the
	// PNG fallback. See https://github.com/Modernizr/Modernizr/blob/master/feature-detects/svg/asimg.js
	if (!document.implementation.hasFeature('http://www.w3.org/TR/SVG11/feature#Image', '1.1')) {
		$( '.mwe-math-fallback-svg-inline, .mwe-math-fallback-svg-display' ).each(function() {
                        img = document.createElement('img');
                        url = this.getAttribute('style').match(/url\('([^']*)'\)/)[1];
                        img.setAttribute('src', url.replace('mode=' + MW_MATH_MATHML, 'mode=' + MW_MATH_PNG));
                        img.setAttribute('class', $( this ).hasClass('mwe-math-fallback-svg-inline') ? 'mwe-math-fallback-png-inline' : 'mwe-math-fallback-png-display');
                        this.parentNode.insertBefore(img, this);
                        $( this ).css( 'display', 'none' );
		});
	}
}( jQuery ) );
