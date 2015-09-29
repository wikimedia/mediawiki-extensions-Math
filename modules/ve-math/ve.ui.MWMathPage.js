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
	var i, ilen, insert, symbol, symbols, $symbols, symbolNode, symbolsNode, tex;

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
			symbolNode = document.createElement( 'div' );
			symbolNode.className = 've-ui-mwMathPage-symbol ' +
				've-ui-mwMathSymbol-' + tex.replace( /[^\w]/g, function ( c ) {
					return '_' + c.charCodeAt( 0 ) + '_';
				}
			);
			symbolNode.className += symbol.wide ? ' ve-ui-mwMathPage-symbol-wide' : '';
			symbolNode.className += symbol.contain ? ' ve-ui-mwMathPage-symbol-contain' : '';
			symbolNode.className += symbol.largeLayout ? ' ve-ui-mwMathPage-symbol-largeLayout' : '';
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
