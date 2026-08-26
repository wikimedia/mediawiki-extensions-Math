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
 * Transform Core-compatible MathML before MathJax parses it.
 *
 * @param {Object} options MathJax filter options
 * @param {Document|Element} options.data MathML DOM to transform
 */
function mmlFilter( { data } ) {
	// From https://github.com/mathjax/MathJax/issues/3540
	transformSmallMatrices( data );
}

module.exports = {
	mmlFilter,
	transformSmallMatrices
};
