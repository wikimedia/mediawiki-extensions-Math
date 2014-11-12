/*!
 * VisualEditor UserInterface MWMathInspector class.
 *
 * @copyright 2011-2014 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/*global ve, OO */

/**
 * MediaWiki math inspector.
 *
 * @class
 * @extends ve.ui.MWLiveExtensionInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMathInspector = function VeUiMWMathInspector( config ) {
	// Parent constructor
	ve.ui.MWLiveExtensionInspector.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathInspector, ve.ui.MWLiveExtensionInspector );

/* Static properties */

ve.ui.MWMathInspector.static.name = 'math';

ve.ui.MWMathInspector.static.icon = 'math';

ve.ui.MWMathInspector.static.size = 'large';

ve.ui.MWMathInspector.static.title = OO.ui.deferMsg( 'math-visualeditor-mwmathinspector-title' );

ve.ui.MWMathInspector.static.nodeModel = ve.dm.MWMathNode;

ve.ui.MWMathInspector.static.dir = 'ltr';

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMathInspector.prototype.initialize = function () {
	// Parent method
	ve.ui.MWMathInspector.super.prototype.initialize.call( this );

	// Position
	this.displaySelect = new OO.ui.ButtonSelectWidget( {
		$: this.$,
		items: [
			new OO.ui.ButtonOptionWidget( 'default', {
				$: this.$,
				icon: 'math-display-default',
				label: ve.msg( 'math-visualeditor-mwmathinspector-display-default' )
			} ),
			new OO.ui.ButtonOptionWidget( 'inline', {
				$: this.$,
				icon: 'math-display-inline',
				label: ve.msg( 'math-visualeditor-mwmathinspector-display-inline' )
			} ),
			new OO.ui.ButtonOptionWidget( 'block', {
				$: this.$,
				icon: 'math-display-block',
				label: ve.msg( 'math-visualeditor-mwmathinspector-display-block' )
			} )
		]
	} );

	this.idInput = new OO.ui.TextInputWidget( { $: this.$	} );

	var inputField = new OO.ui.FieldLayout( this.input, {
			$: this.$,
			align: 'top',
			label: ve.msg( 'math-visualeditor-mwmathinspector-title' )
		} ),
		displayField = new OO.ui.FieldLayout( this.displaySelect, {
			$: this.$,
			align: 'top',
			label: ve.msg( 'math-visualeditor-mwmathinspector-display' )
		} ),
		idField = new OO.ui.FieldLayout( this.idInput, {
			$: this.$,
			align: 'top',
			label: ve.msg( 'math-visualeditor-mwmathinspector-id' )
		} );

	// Initialization
	this.$content.addClass( 've-ui-mwMathInspector-content' );
	this.form.$element.append( inputField.$element, displayField.$element, idField.$element );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathInspector.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWMathInspector.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var display = this.node.getAttribute( 'mw' ).attrs.display || 'default';
			this.displaySelect.selectItem( this.displaySelect.getItemFromData( display ) );
			this.displaySelect.on( 'choose', this.onChangeHandler );
			this.idInput.on( 'change', this.onChangeHandler );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathInspector.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWMathInspector.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.displaySelect.off( 'choose', this.onChangeHandler );
			this.idInput.off( 'change', this.onChangeHandler );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathInspector.prototype.updatePreview = function () {
	var mwData = ve.copy( this.node.getAttribute( 'mw' ) ),
		display = this.displaySelect.getSelectedItem().getData(),
		id = this.idInput.getValue();

	mwData.body.extsrc = this.input.getValue();
	mwData.attrs.display = display !== 'default' ? display : undefined;
	mwData.attrs.id = id || undefined;

	if ( this.visible ) {
		this.getFragment().changeAttributes( { mw: mwData } );
	}
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMathInspector );
