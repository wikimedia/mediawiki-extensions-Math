const MATHML_NS = 'http://www.w3.org/1998/Math/MathML';

/**
 * Restore the non-Core attributes used by MathJax to render small matrices.
 *
 * @param {Document|Element} data MathML DOM to transform
 */
function transformSmallMatrices( data ) {
	const mtables = Array.from( data.getElementsByTagName( 'mtable' ) ).filter( ( mtable ) => (
		mtable.getAttribute( 'class' ) || ''
	).split( /\s+/ ).includes( 'mwe-math-smallmatrix' ) );

	for ( const mtable of mtables ) {
		mtable.setAttribute( 'data-mjx-smallmatrix', 'true' );
		mtable.setAttribute( 'rowspacing', '.2em' );
		mtable.setAttribute( 'columnspacing', '0.333em' );
		const mstyle = mtable.ownerDocument.createElementNS( MATHML_NS, 'mstyle' );
		mstyle.setAttribute( 'scriptlevel', '1' );
		mtable.parentNode.replaceChild( mstyle, mtable );
		mstyle.appendChild( mtable );
	}
}

/**
 * Restore menclose elements represented by Core-compatible mrows.
 *
 * The first child contains the semantic content. The notation is encoded in
 * class names used by the native CSS polyfill.
 *
 * @param {Document|Element} data MathML DOM to transform
 */
function transformMenclose( data ) {
	const mrows = Array.from( data.getElementsByTagName( 'mrow' ) ).filter( ( mrow ) => (
		mrow.getAttribute( 'class' ) || ''
	).split( /\s+/ ).includes( 'menclose' ) );

	for ( const mrow of mrows.reverse() ) {
		const content = Array.from( mrow.childNodes ).find( ( child ) => child.nodeType === 1 );
		const notation = ( mrow.getAttribute( 'class' ) || '' ).split( /\s+/ )
			.filter( ( className ) => className.startsWith( 'menclose-' ) )
			.map( ( className ) => className.slice( 'menclose-'.length ) );
		if ( !content || notation.length === 0 ) {
			continue;
		}

		const menclose = mrow.ownerDocument.createElementNS( MATHML_NS, 'menclose' );
		menclose.setAttribute( 'notation', notation.join( ' ' ) );
		while ( content.firstChild ) {
			menclose.appendChild( content.firstChild );
		}
		mrow.parentNode.replaceChild( menclose, mrow );
	}
}

/**
 * Restore the relative height used for cancelto annotations by MathJax.
 *
 * @param {Document|Element} data MathML DOM to transform
 */
function transformCancelTo( data ) {
	const annotations = Array.from( data.getElementsByTagName( 'mpadded' ) ).filter( ( mpadded ) => (
		mpadded.getAttribute( 'class' ) || ''
	).split( /\s+/ ).includes( 'mwe-math-cancelto' ) );

	for ( const annotation of annotations ) {
		annotation.setAttribute( 'height', '+.1em' );
		const className = ( annotation.getAttribute( 'class' ) || '' ).split( /\s+/ )
			.filter( ( name ) => name && name !== 'mwe-math-cancelto' ).join( ' ' );
		if ( className ) {
			annotation.setAttribute( 'class', className );
		} else {
			annotation.removeAttribute( 'class' );
		}
	}
}

/**
 * Transform Core-compatible MathML before MathJax parses it.
 *
 * @param {Object} options MathJax filter options
 * @param {Document|Element} options.data MathML DOM to transform
 */
function mmlFilter( { data } ) {
	// From https://github.com/mathjax/MathJax/issues/3540
	transformSmallMatrices( data );
	transformMenclose( data );
	transformCancelTo( data );
}

module.exports = {
	mmlFilter,
	transformCancelTo,
	transformMenclose,
	transformSmallMatrices
};
