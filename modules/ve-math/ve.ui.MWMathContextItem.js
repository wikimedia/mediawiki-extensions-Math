/*!
 * VisualEditor MWMathContextItem class.
 *
 * @copyright 2015 VisualEditor Team and others; see http://ve.mit-license.org
 */

/**
 * Context item for a math node.
 *
 * @class
 * @extends ve.ui.LinearContextItem
 *
 * @param {ve.ui.Context} context Context item is in
 * @param {ve.dm.Model} model Model item is related to
 * @param {Object} config Configuration options
 */
ve.ui.MWMathContextItem = function VeUiMWMathContextItem() {
	// Parent constructor
	ve.ui.MWMathContextItem.super.apply( this, arguments );

	this.quickEditButton = new OO.ui.ButtonWidget( {
		label: ve.msg( 'math-visualeditor-mwmathcontextitem-quickedit' ),
		flags: [ 'progressive' ]
	} );

	this.actionButtons.addItems( [ this.quickEditButton ], 0 );

	this.quickEditButton.connect( this, { click: 'onInlineEditButtonClick' } );

	// Initialization
	this.$element.addClass( 've-ui-mwMathContextItem' );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathContextItem, ve.ui.LinearContextItem );

/* Static Properties */

ve.ui.MWMathContextItem.static.name = 'math';

ve.ui.MWMathContextItem.static.icon = 'math';

ve.ui.MWMathContextItem.static.label = OO.ui.deferMsg( 'math-visualeditor-mwmathinspector-title' );

ve.ui.MWMathContextItem.static.modelClasses = [ ve.dm.MWMathNode ];

ve.ui.MWMathContextItem.static.embeddable = false;

ve.ui.MWMathContextItem.static.commandName = 'mathDialog';

/* Methods */

/**
 * Handle inline edit button click events.
 */
ve.ui.MWMathContextItem.prototype.onInlineEditButtonClick = function () {
	this.context.getSurface().executeCommand( 'mathInspector' );
};

/* Registration */

ve.ui.contextItemFactory.register( ve.ui.MWMathContextItem );
