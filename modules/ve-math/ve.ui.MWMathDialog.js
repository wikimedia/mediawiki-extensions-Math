/*!
 * VisualEditor user interface MWMathDialog class.
 *
 * @copyright 2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Dialog for inserting and editing formulas.
 *
 * @class
 * @extends ve.ui.MWExtensionDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */

ve.ui.MWMathDialog = function VeUiMWMathDialog( config ) {
	// Parent constructor
	ve.ui.MWMathDialog.super.call( this, config );

	// Late bind onChangeHandler to a debounced updatePreview
	this.onChangeHandler = ve.debounce( this.updatePreview.bind( this ), 250 );

	// Properties?

	// Classes?
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathDialog, ve.ui.MWExtensionDialog );

/* Static properties */

ve.ui.MWMathDialog.static.name = 'math';

ve.ui.MWMathDialog.static.icon = 'math';

ve.ui.MWMathDialog.static.title = OO.ui.deferMsg( 'math-visualeditor-mwmathdialog-title' ); // Add this

ve.ui.MWMathDialog.static.size = 'larger';

ve.ui.MWMathDialog.static.modelClasses = [ ve.dm.MWMathNode ];

ve.ui.MWMathDialog.static.dir = 'ltr';

ve.ui.MWMathDialog.static.symbols = null;

/* static methods */

// Add documentation
ve.ui.MWMathDialog.static.setSymbols = function ( symbols ) {
	this.symbols = symbols;
};

/* Methods */

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.initialize = function () {
	var menuPanel, inputField, displayField, idField, category,
		dialog = this;

	// Parent method
	ve.ui.MWMathDialog.super.prototype.initialize.call( this );

	// Layout for symbol picker (menu) and inspector form (content)
	this.menuLayout = new OO.ui.MenuLayout( {
		menuPosition: 'before'
	} );

	// menuLayout content
	menuPanel = new OO.ui.PanelLayout( {
		padded: true,
		expanded: true,
		scrollable: true
	} );

	this.$previewElementContainer = $( '<div>' ).addClass(
		've-ui-mwMathDialog-preview-element-container'
	);

	this.input = new ve.ui.WhitespacePreservingTextInputWidget( {
		multiline: true,
		rows: 10
	} );

	// Things from inspector
	this.displaySelect = new OO.ui.ButtonSelectWidget( {
		items: [
			new OO.ui.ButtonOptionWidget( {
				data: 'default',
				icon: 'math-display-default',
				label: ve.msg( 'math-visualeditor-mwmathinspector-display-default' )
			} ),
			new OO.ui.ButtonOptionWidget( {
				data: 'inline',
				icon: 'math-display-inline',
				label: ve.msg( 'math-visualeditor-mwmathinspector-display-inline' )
			} ),
			new OO.ui.ButtonOptionWidget( {
				data: 'block',
				icon: 'math-display-block',
				label: ve.msg( 'math-visualeditor-mwmathinspector-display-block' )
			} )
		]
	} );

	this.idInput = new OO.ui.TextInputWidget();

	inputField = new OO.ui.FieldLayout( this.input, {
		align: 'top',
		label: ve.msg( 'math-visualeditor-mwmathinspector-title' )
	} );
	displayField = new OO.ui.FieldLayout( this.displaySelect, {
		align: 'top',
		label: ve.msg( 'math-visualeditor-mwmathinspector-display' )
	} );
	idField = new OO.ui.FieldLayout( this.idInput, {
		align: 'top',
		label: ve.msg( 'math-visualeditor-mwmathinspector-id' )
	} );

	// menuLayout menu
	this.bookletLayout = new OO.ui.BookletLayout( {
		menuPosition: 'top',
		outlined: true,
		continuous: true
	} );
	this.pages = [];
	this.symbolsPromise = mw.loader.using( 'ext.math.visualEditor.symbols' ).done( function () {
		var symbols = dialog.constructor.static.symbols;
		for ( category in symbols ) {
			dialog.pages.push(
				new ve.ui.MWMathPage( ve.msg( category ), {
					label: ve.msg( category ),
					symbols: symbols[ category ]
				} )
			);
		}
		dialog.bookletLayout.addPages( dialog.pages );
		dialog.bookletLayout.$element.on(
			'click',
			'.ve-ui-mwMathPage-symbol',
			dialog.onListClick.bind( dialog )
		);
		// Appending everything
		dialog.menuLayout.$menu.append(
			dialog.bookletLayout.$element
		);

		dialog.menuLayout.$content.append(
			menuPanel.$element.append(
				dialog.$previewElementContainer,
				inputField.$element,
				displayField.$element,
				idField.$element
			)
		);

		dialog.menuLayout.$content;

		dialog.$body
			.addClass( 've-ui-mwMathDialog-content' )
			.append( dialog.menuLayout.$element );
	} );

};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWMathDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var display = this.selectedNode.getAttribute( 'mw' ).attrs.display || 'default';
			this.previewElement = new ve.ui.PreviewElement(
				this.selectedNode
			);
			this.$previewElementContainer.append( this.previewElement.$element );
			// Update mwdata for this.selectedNode
			// update previewElement.model
			// call updatePreview on the new previewElement.model
			this.input.on( 'change', this.onChangeHandler );
			this.displaySelect.selectItemByData( display );
			// this.displaySelect.on( 'choose', this.onChangeHandler );
			return this.symbolsPromise;
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWMathDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			// this.displaySelect.off( 'choose', this.onChangeHandler );
			this.$previewElementContainer.empty();
		}, this );
};

// Add documentation
ve.ui.MWMathDialog.prototype.updatePreview = function () {
	var mwData = ve.copy( this.selectedNode.getAttribute( 'mw' ) );

	this.updateMwData( mwData );

	this.getFragment().changeAttributes( { mw: mwData } );

	this.previewElement.updatePreview();
};

// /**
//  * @inheritdoc
//  */
// ve.ui.MWMathDialog.prototype.updateMwData = function ( mwData ) {
// 	var display, id;

// 	// Parent method
// 	ve.ui.MWMathDialog.super.prototype.updateMwData.call( this, mwData );

// 	display = this.displaySelect.getSelectedItem().getData();
// 	id = this.idInput.getValue();

// 	mwData.attrs.display = display !== 'default' ? display : undefined;
// 	mwData.attrs.id = id || undefined;
// };

// HACK: until we know what the first child is
ve.ui.MWMathDialog.prototype.getBodyHeight = function () {
	return 500;
};

ve.ui.MWMathDialog.prototype.onListClick = function ( e ) {
	var insert = $( e.target ).data( 'insert' ),
		selectStart = insert.search( /__/ ),
		selectEnd,
		insertStart = this.input.$input[ 0 ].selectionStart, // Use TextInputWidget API
		insertEnd = this.input.$input[ 0 ].selectionEnd,
		oldValue = this.input.getValue();

	if ( selectStart === -1 ) {
		selectStart = insert.length;
		selectEnd = insert.length;
	} else {
		selectEnd = selectStart + 1;
	}

	this.input.setValue(
		oldValue.slice( 0, insertStart ) + insert.replace( /__/g, 'a' ) +  oldValue.slice( insertEnd )
	);
	this.input.focus().selectRange( insertStart + selectStart, insertStart + selectEnd );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMathDialog );
