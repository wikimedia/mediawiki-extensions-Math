/*!
 * VisualEditor user interface MWLatexPage class.
 *
 * @copyright 2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Latex dialog symbols page
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 */
ve.ui.MWLatexPage = function VeUiMWLatexPage( name, config ) {
	var i, ilen, j, jlen, symbol, symbols, $symbols,
		symbolNode, symbolsNode, tex, classes;

	// Parent constructor
	ve.ui.MWLatexPage.super.call( this, name, config );

	this.label = config.label;

	symbols = config.symbols;
	$symbols = $( '<div>' ).addClass( 've-ui-specialCharacterPage-characters' );
	symbolsNode = $symbols[ 0 ];

	// Avoiding jQuery wrappers as advised in ve.ui.SpecialCharacterPage
	for ( i = 0, ilen = symbols.length; i < ilen; i++ ) {
		symbol = symbols[ i ];
		if ( !symbol.notWorking && !symbol.duplicate ) {
			tex = symbol.tex || symbol.insert;
			classes = [ 've-ui-mwLatexPage-symbol' ];
			classes.push(
				've-ui-mwLatexSymbol-' + tex.replace( /[^\w]/g, function ( c ) {
					return '_' + c.charCodeAt( 0 ) + '_';
				} )
			);
			if ( symbol.width ) {
				classes.push( 've-ui-mwLatexPage-symbol-' + symbol.width );
			}
			if ( symbol.contain ) {
				classes.push( 've-ui-mwLatexPage-symbol-contain' );
			}
			if ( symbol.largeLayout ) {
				classes.push( 've-ui-mwLatexPage-symbol-largeLayout' );
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
		.addClass( 've-ui-mwLatexPage' )
		.append( $( '<h3>' ).text( name ), $symbols );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWLatexPage, OO.ui.PageLayout );

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWLatexPage.prototype.setupOutlineItem = function ( outlineItem ) {
	ve.ui.MWLatexPage.super.prototype.setupOutlineItem.call( this, outlineItem );
	this.outlineItem.setLabel( this.label );
	this.outlineItem.$element.addClass( 've-ui-mwLatexPage-outline' );
};
