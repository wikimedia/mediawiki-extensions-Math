( function ( $ ) {
	'use strict';
	var img, url;

	function insertImg( png ) {
		$( '.mwe-math-fallback-image-inline, .mwe-math-fallback-image-display' ).each( function ( ) {
			// Create a new image to use as the fallback.
			img = document.createElement( 'img' );
			url = this.style.backgroundImage.match( /url\(\s*(['"]?)([^\1\)]*)\1\s*\)/ )[ 2 ];
			if ( png ) {
				url = url.replace( 'media/math/render/svg/', 'media/math/render/png/' );
			}
			img.setAttribute( 'src', url );
			img.setAttribute( 'class', 'tex mwe-math-fallback-image-' + ( $( this ).hasClass( 'mwe-math-fallback-image-inline' ) ? 'inline' : 'display' ) );
			img.setAttribute( 'aria-hidden', 'true' );
			this.parentNode.insertBefore( img, this );

			// Hide the old SVG fallback.
			$( this ).css( 'display', 'none' );
		} );
	}

	// If MathPlayer is installed we show the MathML rendering.
	if ( navigator.userAgent.indexOf( 'MathPlayer' ) > -1 ) {
		$( '.mwe-math-mathml-a11y' ).removeClass( 'mwe-math-mathml-a11y' );
		$( '.mwe-math-fallback-image-inline, .mwe-math-fallback-image-display' ).css( 'display', 'none' );
		return;
	}

	// We verify whether SVG as <img> is supported and otherwise use the
	// PNG fallback. See https://github.com/Modernizr/Modernizr/blob/master/feature-detects/svg/asimg.js
	if ( !document.implementation.hasFeature( 'http://www.w3.org/TR/SVG11/feature#Image', '1.1' ) ) {
		insertImg( true );
	} else if ( $.client.profile().name.match( /msie|edge/ ) ) {
		// For all IE versions the meta tags are rendered blurry, while img tags are rendered fine.
		insertImg( false );
	}
}( jQuery ) );
