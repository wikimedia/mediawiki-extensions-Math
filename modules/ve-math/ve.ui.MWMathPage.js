/*!
 * VisualEditor user interface MWMathPage class.
 *
 * @copyright 2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Math dialog symbols page
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMathPage = function VeUiMWMathPage( name, config ) {
	var i, ilen, j, jlen, insert, symbol, symbols, $symbols,
		symbolNode, symbolsNode, tex, classes;

	// Parent constructor
	ve.ui.MWMathPage.super.call( this, name, config );

	this.label = config.label;

	symbols = config.symbols;
	$symbols = $( '<div>' ).addClass( 've-ui-specialCharacterPage-characters' );
	symbolsNode = $symbols[ 0 ];

	// Avoiding jQuery wrappers as advised in ve.ui.SpecialCharacterPage
	for ( i = 0, ilen = symbols.length; i < ilen; i++ ) {
		symbol = symbols[ i ];
		if ( !symbol.notWorking && !symbol.duplicate ) {
			tex = symbol.tex;
			insert = symbol.insert;
			classes = [ 've-ui-mwMathPage-symbol' ];
			classes.push(
				've-ui-mwMathSymbol-' + tex.replace( /[^\w]/g, function ( c ) {
					return '_' + c.charCodeAt( 0 ) + '_';
				} )
			);
			if ( symbol.wide ) {
				classes.push( 've-ui-mwMathPage-symbol-wide' );
			}
			if ( symbol.contain ) {
				classes.push( 've-ui-mwMathPage-symbol-contain' );
			}
			if ( symbol.largeLayout ) {
				classes.push( 've-ui-mwMathPage-symbol-largeLayout' );
			}
			symbolNode = document.createElement( 'div' );
			for ( j = 0, jlen = classes.length; j < jlen; j++ ) {
				symbolNode.classList.add( classes[ j ] );
			}
			$.data( symbolNode, 'symbol', symbol );
			symbolsNode.appendChild( symbolNode );
		}
	}

	this.$element
		.addClass( 've-ui-mwMathPage' )
		.append( $( '<h3>' ).text( name ), $symbols );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathPage, OO.ui.PageLayout );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMathPage.prototype.setupOutlineItem = function ( outlineItem ) {
	ve.ui.MWMathPage.super.prototype.setupOutlineItem.call( this, outlineItem );
	this.outlineItem.setLabel( this.label );
	this.outlineItem.$element.addClass( 've-ui-mwMathPage-outline' );
};
