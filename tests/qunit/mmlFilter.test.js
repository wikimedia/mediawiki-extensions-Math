'use strict';

const { mmlFilter } = require( 'ext.math.mathjax/ext.math.mathjax.mml.js' );

const MATHML_NS = 'http://www.w3.org/1998/Math/MathML';

function parseMathML( mathML ) {
	return new DOMParser().parseFromString( mathML, 'application/xml' );
}

function normalizeNode( node ) {
	if ( node.nodeType === Node.DOCUMENT_NODE ) {
		return normalizeNode( node.documentElement );
	}
	if ( node.nodeType === Node.TEXT_NODE ) {
		return node.nodeValue;
	}
	return {
		name: node.localName,
		namespace: node.namespaceURI,
		attributes: Array.from( node.attributes ).map( ( attribute ) => [
			attribute.name,
			attribute.value
		] ).sort( ( left, right ) => left[ 0 ].localeCompare( right[ 0 ] ) ),
		children: Array.from( node.childNodes ).map( normalizeNode )
	};
}

QUnit.module( 'ext.math.mathjax.mml', () => {

	QUnit.test.each( 'filter', {
		'transforms a small matrix and cancellation in the same expression': {
			input: '<math xmlns="http://www.w3.org/1998/Math/MathML"><mrow><mtable class="mwe-math-smallmatrix"><mtr><mtd><mi>x</mi></mtd></mtr></mtable><mo>+</mo><mrow class="menclose menclose-updiagonalstrike"><mrow><mi>y</mi></mrow></mrow></mrow></math>',
			expected: '<math xmlns="http://www.w3.org/1998/Math/MathML"><mrow><mstyle scriptlevel="1"><mtable class="mwe-math-smallmatrix" data-mjx-smallmatrix="true" rowspacing=".2em" columnspacing="0.333em"><mtr><mtd><mi>x</mi></mtd></mtr></mtable></mstyle><mo>+</mo><menclose notation="updiagonalstrike"><mi>y</mi></menclose></mrow></math>'
		},
		'restores nested cancellation and a cancelto annotation': {
			input: '<math xmlns="http://www.w3.org/1998/Math/MathML"><mrow class="menclose menclose-updiagonalstrike"><mrow><mrow class="menclose menclose-downdiagonalstrike"><mrow><mi>x</mi></mrow></mrow><msup><mrow class="menclose menclose-northeastarrow"><mrow><mi>y</mi></mrow></mrow><mpadded class="mwe-math-cancelto" depth="-.1em" voffset=".1em"><mn>0</mn></mpadded></msup></mrow></mrow></math>',
			expected: '<math xmlns="http://www.w3.org/1998/Math/MathML"><menclose notation="updiagonalstrike"><menclose notation="downdiagonalstrike"><mi>x</mi></menclose><msup><menclose notation="northeastarrow"><mi>y</mi></menclose><mpadded depth="-.1em" voffset=".1em" height="+.1em"><mn>0</mn></mpadded></msup></menclose></math>'
		}
	}, ( assert, testCase ) => {
		const document = parseMathML( testCase.input );

		mmlFilter( { data: document } );

		assert.deepEqual(
			normalizeNode( document ),
			normalizeNode( parseMathML( testCase.expected ) )
		);
	} );

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

	QUnit.test( 'restores menclose from its Core-compatible representation', ( assert ) => {
		const document = parseMathML( `<math xmlns="${ MATHML_NS }"><mrow class="menclose menclose-northeastarrow"><mrow><mi>x</mi></mrow></mrow></math>` );

		mmlFilter( { data: document } );

		const menclose = document.getElementsByTagName( 'menclose' )[ 0 ];
		assert.strictEqual( menclose.getAttribute( 'notation' ), 'northeastarrow' );
		assert.strictEqual( menclose.childNodes.length, 1 );
		assert.strictEqual( menclose.firstChild.localName, 'mi' );
		assert.strictEqual( menclose.textContent, 'x' );
		assert.strictEqual( document.getElementsByTagName( 'mrow' ).length, 0 );
	} );

	QUnit.test( 'restores nested menclose elements', ( assert ) => {
		const document = parseMathML( `<math xmlns="${ MATHML_NS }"><mrow class="menclose menclose-updiagonalstrike"><mrow><mrow class="menclose menclose-downdiagonalstrike"><mrow><mi>x</mi></mrow></mrow></mrow></mrow></math>` );

		mmlFilter( { data: document } );

		const menclose = document.getElementsByTagName( 'menclose' );
		assert.strictEqual( menclose.length, 2 );
		assert.strictEqual( menclose[ 0 ].getAttribute( 'notation' ), 'updiagonalstrike' );
		assert.strictEqual( menclose[ 1 ].getAttribute( 'notation' ), 'downdiagonalstrike' );
		assert.strictEqual( menclose[ 1 ].textContent, 'x' );
	} );

	QUnit.test( 'restores the MathJax cancelto annotation height', ( assert ) => {
		const document = parseMathML( `<math xmlns="${ MATHML_NS }"><mpadded class="mwe-math-cancelto" depth="-.1em" voffset=".1em"><mi>x</mi></mpadded></math>` );

		mmlFilter( { data: document } );

		const annotation = document.getElementsByTagName( 'mpadded' )[ 0 ];
		assert.strictEqual( annotation.getAttribute( 'height' ), '+.1em' );
		assert.strictEqual( annotation.getAttribute( 'class' ), null );
	} );
} );
