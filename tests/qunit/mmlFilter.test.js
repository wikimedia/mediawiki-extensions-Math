'use strict';

const { mmlFilter } = require( 'ext.math.mathjax/ext.math.mathjax.mml.js' );

const MATHML_NS = 'http://www.w3.org/1998/Math/MathML';

function parseMathML( mathML ) {
	return new DOMParser().parseFromString( mathML, 'application/xml' );
}

QUnit.module( 'ext.math.mathjax.mml', () => {
	QUnit.test( 'leave regular matrices unchanged', ( assert ) => {
		const document = parseMathML( `<math xmlns="${ MATHML_NS }"><mtable><mtr><mtd><mi>x</mi></mtd></mtr></mtable></math>` );
		const mtable = document.getElementsByTagName( 'mtable' )[ 0 ];
		const parent = mtable.parentNode;

		mmlFilter( { data: document } );

		assert.strictEqual( mtable.parentNode, parent );
		assert.strictEqual( mtable.getAttribute( 'rowspacing' ), null );
		assert.strictEqual( mtable.getAttribute( 'columnspacing' ), null );
		assert.strictEqual( mtable.getAttribute( 'data-mjx-smallmatrix' ), null );
	} );

	QUnit.test( 'restore the MathJax small-matrix representation', ( assert ) => {
		const document = parseMathML( `<math xmlns="${ MATHML_NS }"><mtable class="foo mwe-math-smallmatrix bar"><mtr><mtd><mi>x</mi></mtd></mtr></mtable></math>` );
		const mtable = document.getElementsByTagName( 'mtable' )[ 0 ];

		mmlFilter( { data: document } );

		const mstyle = mtable.parentNode;
		assert.strictEqual( mstyle.localName, 'mstyle' );
		assert.strictEqual( mstyle.namespaceURI, MATHML_NS );
		assert.strictEqual( mstyle.getAttribute( 'scriptlevel' ), '1' );
		assert.strictEqual( mstyle.childNodes.length, 1 );
		assert.strictEqual( mtable.getAttribute( 'class' ), 'foo mwe-math-smallmatrix bar' );
		assert.strictEqual( mtable.getAttribute( 'data-mjx-smallmatrix' ), 'true' );
		assert.strictEqual( mtable.getAttribute( 'rowspacing' ), '.2em' );
		assert.strictEqual( mtable.getAttribute( 'columnspacing' ), '0.333em' );
		assert.strictEqual( mtable.getElementsByTagName( 'mi' )[ 0 ].textContent, 'x' );
	} );

	QUnit.test( 'transform every small matrix in the input', ( assert ) => {
		const document = parseMathML( `<math xmlns="${ MATHML_NS }"><mrow><mtable class="mwe-math-smallmatrix"/><mo>+</mo><mtable class="mwe-math-smallmatrix"/></mrow></math>` );

		mmlFilter( { data: document } );

		const mtables = Array.from( document.getElementsByTagName( 'mtable' ) );
		assert.strictEqual( mtables.length, 2 );
		for ( const mtable of mtables ) {
			assert.strictEqual( mtable.parentNode.localName, 'mstyle' );
		}
	} );
} );
