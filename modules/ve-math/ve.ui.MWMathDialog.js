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

	// Properties?

	// Classes?
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathDialog, ve.ui.MWExtensionPreviewDialog );

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
	var formulaPanel, inputField, displayField, idField, category,
		formulaCard, optionsCard,
		dialog = this;

	// Parent method
	ve.ui.MWMathDialog.super.prototype.initialize.call( this );

	// Layout for symbol picker (menu) and inspector form (content)
	this.menuLayout = new OO.ui.MenuLayout( {
		menuPosition: 'bottom',
		classes: [ 've-ui-mwMathDialog-menuLayout' ]
	} );

	// Index layout
	this.indexLayout = new OO.ui.IndexLayout( {
		scrollable: false,
		expanded: true
	} );

	// Cards
	formulaCard = new OO.ui.CardLayout( 'formula', {
		label: ve.msg( 'math-visualeditor-mwmathdialog-card-formula' ),
		expandable: false,
		scrollable: false,
		padded: true
	} );
	optionsCard = new OO.ui.CardLayout( 'options', {
		label: ve.msg( 'math-visualeditor-mwmathdialog-card-options' ),
		expandable: false,
		scrollable: false,
		padded: true
	} );

	this.indexLayout.addCards( [
		formulaCard,
		optionsCard
	] );

	this.previewElement.$element.addClass(
		've-ui-mwMathDialog-preview'
	);

	this.input = new ve.ui.MWAceEditorWidget( {
		multiline: true,
		rows: 7
	} ).setLanguage( 'latex' );

	this.input.togglePrintMargin( false );

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

	// Formula layout
	formulaPanel = new OO.ui.PanelLayout( {
		padded: true
	} );

	// menuLayout menu
	this.bookletLayout = new OO.ui.BookletLayout( {
		menuPosition: 'before',
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
			formulaPanel.$element.append(
				dialog.previewElement.$element,
				inputField.$element
			)
		);

		formulaCard.$element.append(
			dialog.menuLayout.$element
		);
		optionsCard.$element.append(
			displayField.$element,
			idField.$element
		);

		dialog.$body
			.addClass( 've-ui-mwMathDialog-content' )
			.append( dialog.indexLayout.$element );
	} );

};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getSetupProcess = function ( data ) {
	return ve.ui.MWMathDialog.super.prototype.getSetupProcess.call( this, data )
		.next( function () {
			var display = ( this.selectedNode && this.selectedNode.getAttribute( 'mw' ).attrs.display ) || 'default';
			this.input.on( 'change', this.onChangeHandler );
			this.displaySelect.selectItemByData( display );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWMathDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			return this.symbolsPromise;
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWMathDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.input.off( 'change', this.onChangeHandler );
		}, this );
};

// HACK: until we know what the first child is
ve.ui.MWMathDialog.prototype.getBodyHeight = function () {
	return 600;
};

ve.ui.MWMathDialog.prototype.onListClick = function ( e ) {
	var insert = $( e.target ).data( 'insert' ),
		selectStart = insert.search( /__/ ),
		selectEnd,
		insertRange = this.input.getRange(),
		oldValue = this.input.getValue();

	if ( selectStart === -1 ) {
		selectStart = insert.length;
		selectEnd = insert.length;
	} else {
		selectEnd = selectStart + 1;
	}

	this.input.setValue(
		oldValue.slice( 0, insertRange.from ) + insert.replace( /__/g, 'a' ) +  oldValue.slice( insertRange.to )
	);
	this.input.focus().selectRange( insertRange.from + selectStart, insertRange.from + selectEnd );
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMathDialog );
