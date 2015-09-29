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
	var i, ilen, insert, symbol, symbols, $symbols, symbolNode, symbolsNode;

	// Parent constructor
	ve.ui.MWMathPage.super.call( this, name, config );

	this.label = config.label;

	symbols = config.symbols;
	$symbols = $( '<div>' ).addClass( 've-ui-specialCharacterPage-characters' );
	symbolsNode = $symbols[ 0 ];

	// Avoiding jQuery wrappers as advised in ve.ui.SpecialCharacterPage
	for ( i = 0, ilen = symbols.length; i < ilen; i++ ) {
		symbol = symbols[ i ].tex;
		insert = symbols[ i ].insert;
		symbolNode = document.createElement( 'div' );
		symbolNode.className = 've-ui-mwMathPage-symbol ' +
			've-ui-mwMathSymbol-' + symbol.replace( /[^\w]/g, function ( c ) {
				return '_' + c.charCodeAt( 0 ) + '_';
			}
		);
		if ( symbols[ i ].wide ) {
			symbolNode.className += ' ve-ui-mwMathPage-symbol-wide';
		}
		$.data( symbolNode, 'insert', insert );
		symbolsNode.appendChild( symbolNode );
	}

	this.$element
		.addClass( 've-ui-mwMathPage' )
		.append( $( '<h3>' ).text( name ), $symbols );
};

OO.inheritClass( ve.ui.MWMathPage, OO.ui.PageLayout );

ve.ui.MWMathPage.prototype.setupOutlineItem = function ( outlineItem ) {
	ve.ui.MWMathPage.super.prototype.setupOutlineItem.call( this, outlineItem );
	this.outlineItem.setLabel( this.label );
	this.outlineItem.$element.addClass( 've-ui-mwMathPage-outline' );
};
