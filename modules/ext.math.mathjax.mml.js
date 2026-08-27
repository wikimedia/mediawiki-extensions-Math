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
 * Restore the non-Core table borders used by MathJax.
 *
 * @param {Document|Element} data MathML DOM to transform
 */
function transformMatrixBorders( data ) {
	const mtables = Array.from( data.getElementsByTagName( 'mtable' ) );

	for ( const mtable of mtables ) {
		const rows = Array.from( mtable.childNodes ).filter( ( child ) => child.localName === 'mtr' );
		const cells = rows.map( ( row ) => Array.from( row.childNodes )
			.filter( ( child ) => child.localName === 'mtd' ) );
		const firstRow = cells[ 0 ] || [];
		if ( firstRow.length === 0 ) {
			continue;
		}

		const hasBorderClass = ( cell, side ) => cell.classList.contains( `mwe-math-matrix-${ side }` );

		// BaseParsing::matrix marks each boundary on both cells that share it,
		// so reading one side of each reverses the encoding.
		const rowlines = cells.slice( 1 ).map( ( row ) => (
			hasBorderClass( row[ 0 ], 'top' ) ? 'solid' : 'none'
		) );
		const columnlines = firstRow.slice( 1 ).map( ( cell ) => (
			hasBorderClass( cell, 'left' ) ? 'solid' : 'none'
		) );
		const notation = [
			[ 'top', firstRow[ 0 ] ],
			[ 'bottom', cells[ cells.length - 1 ][ 0 ] ],
			[ 'left', firstRow[ 0 ] ],
			[ 'right', firstRow[ firstRow.length - 1 ] ]
		]
			.filter( ( [ side, cell ] ) => hasBorderClass( cell, side ) )
			.map( ( [ side ] ) => side );

		if ( notation.length === 0 && !rowlines.includes( 'solid' ) &&
			!columnlines.includes( 'solid' )
		) {
			continue;
		}

		if ( rowlines.includes( 'solid' ) ) {
			mtable.setAttribute( 'rowlines', rowlines.join( ' ' ) );
		}
		if ( columnlines.includes( 'solid' ) ) {
			mtable.setAttribute( 'columnlines', columnlines.join( ' ' ) );
		}
		// MathJax's own array defaults, dropped by the Core output. MathJax 4.1.0
		// swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1942-1949
		mtable.setAttribute( 'columnspacing', '1em' );
		mtable.setAttribute( 'rowspacing', '4pt' );
		mtable.setAttribute( 'framespacing', '.5em .125em' );

		for ( const cell of cells.flat() ) {
			cell.classList.remove(
				'mwe-math-matrix-top',
				'mwe-math-matrix-right',
				'mwe-math-matrix-bottom',
				'mwe-math-matrix-left'
			);
			if ( cell.classList.length === 0 ) {
				cell.removeAttribute( 'class' );
			}
		}

		if ( notation.length === 4 ) {
			mtable.setAttribute( 'frame', 'solid' );
			continue;
		}

		// frame cannot draw a subset of the edges, so menclose supplies them and
		// data-frame-styles keeps framespacing live. Same rule as MathJax 4.1.0
		// swh:1:cnt:960fa3a2a557e1777df38426031a3ca90df604f2;lines=1144-1160
		mtable.setAttribute( 'data-frame-styles', '' );
		if ( notation.length > 0 ) {
			const menclose = mtable.ownerDocument.createElementNS( MATHML_NS, 'menclose' );
			menclose.setAttribute( 'notation', notation.join( ' ' ) );
			menclose.setAttribute( 'data-padding', '0' );
			mtable.parentNode.replaceChild( menclose, mtable );
			menclose.appendChild( mtable );
		}
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
	transformMatrixBorders( data );
	transformMenclose( data );
	transformCancelTo( data );
}

module.exports = {
	mmlFilter,
	transformCancelTo,
	transformMatrixBorders,
	transformMenclose,
	transformSmallMatrices
};
