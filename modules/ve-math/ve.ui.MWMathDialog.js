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
 * @extends ve.ui.MWExtensionPreviewDialog
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */

ve.ui.MWMathDialog = function VeUiMWMathDialog( config ) {
	// Parent constructor
	ve.ui.MWMathDialog.super.call( this, config );

};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathDialog, ve.ui.MWExtensionPreviewDialog );

/* Static properties */

ve.ui.MWMathDialog.static.name = 'mathDialog';

ve.ui.MWMathDialog.static.title = OO.ui.deferMsg( 'math-visualeditor-mwmathdialog-title' );

ve.ui.MWMathDialog.static.size = 'larger';

ve.ui.MWMathDialog.static.modelClasses = [ ve.dm.MWMathNode ];

ve.ui.MWMathDialog.static.dir = 'ltr';

ve.ui.MWMathDialog.static.symbols = null;

/* static methods */

/**
 * Set the symbols property
 *
 * @param {Object} symbols The math symbols and their group names
 */
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

	// Layout for the formula inserter (formula card) and options form (options card)
	this.indexLayout = new OO.ui.IndexLayout( {
		scrollable: false,
		expanded: true
	} );

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

	// Layout for symbol picker (menu) and input and preview (content)
	this.menuLayout = new OO.ui.MenuLayout( {
		menuPosition: 'bottom',
		classes: [ 've-ui-mwMathDialog-menuLayout' ]
	} );

	this.previewElement.$element.addClass(
		've-ui-mwMathDialog-preview'
	);

	this.input = new ve.ui.MWAceEditorWidget( {
		multiline: true,
		rows: 1, // This will be recalculated later in onWindowManagerResize
		autocomplete: 'live'
	} ).setLanguage( 'latex' );

	this.input.togglePrintMargin( false );

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

	formulaPanel = new OO.ui.PanelLayout( {
		padded: true
	} );

	// Layout for the symbol picker
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

		// Append everything
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
			var attributes = this.selectedNode && this.selectedNode.getAttribute( 'mw' ).attrs,
				display = attributes && attributes.display || 'default',
				id = attributes && attributes.id || '';

			// Populate form
			this.displaySelect.selectItemByData( display );
			this.idInput.setValue( id );

			// Add event handlers
			this.input.on( 'change', this.onChangeHandler );
			this.displaySelect.on( 'choose', this.onChangeHandler );
			this.idInput.on( 'change', this.onChangeHandler );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getReadyProcess = function ( data ) {
	return ve.ui.MWMathDialog.super.prototype.getReadyProcess.call( this, data )
		.next( function () {
			return this.symbolsPromise;
		}, this )
		.next( function () {
			// Resize the input once the dialog has been appended
			this.input.adjustSize( true ).focus().moveCursorToEnd();
			this.getManager().connect( this, { resize: 'onWindowManagerResize' } );
			this.onWindowManagerResize();
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getTeardownProcess = function ( data ) {
	return ve.ui.MWMathDialog.super.prototype.getTeardownProcess.call( this, data )
		.first( function () {
			this.input.off( 'change', this.onChangeHandler );
			this.displaySelect.off( 'choose', this.onChangeHandler );
			this.idInput.off( 'change', this.onChangeHandler );
			this.getManager().disconnect( this );
		}, this );
};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.updateMwData = function ( mwData ) {
	var display, id;

	// Parent method
	ve.ui.MWMathDialog.super.prototype.updateMwData.call( this, mwData );

	// Get data from dialog
	display = this.displaySelect.getSelectedItem().getData();
	id = this.idInput.getValue();

	// Update attributes
	mwData.attrs.display = display !== 'default' ? display : undefined;
	mwData.attrs.id = id || undefined;
};

/**
 * @inheritdoc
 */
ve.ui.MWMathDialog.prototype.getBodyHeight = function () {
	return 600;
};

/**
 * Handle the window resize event
 */
ve.ui.MWMathDialog.prototype.onWindowManagerResize = function () {
	var dialog = this;
	this.input.loadingPromise.done( function () {
		var availableSpace, maxInputHeight, singleLineHeight, minRows,
			border = 1,
			padding = 3,
			borderAndPadding = 2 * ( border + padding );

		// Toggle short mode as necessary
		// NB a change of mode triggers a transition...
		dialog.menuLayout.$element.toggleClass(
			've-ui-mwMathDialog-menuLayout-short', dialog.menuLayout.$element.height() < 450
		);

		// ...So wait for the possible menuLayout transition to finish
		setTimeout( function () {
			// Give the input the right number of rows to fit the space
			availableSpace = dialog.menuLayout.$content.height() - dialog.input.$element.position().top;
			singleLineHeight = 19;
			maxInputHeight = availableSpace - borderAndPadding;
			minRows = Math.floor( maxInputHeight / singleLineHeight );
			dialog.input.setMinRows( minRows );
		}, 250 );
	} );
};

/**
 * Handle the click event on the list
 *
 * @param {jQuery.Event} e Mouse click event
 */
ve.ui.MWMathDialog.prototype.onListClick = function ( e ) {
	var symbol = $( e.target ).data( 'symbol' ),
		encapsulate = symbol.encapsulate,
		insert = symbol.insert,
		range = this.input.getRange();

	if ( encapsulate ) {
		if ( range.from === range.to ) {
			this.input.insertContent( encapsulate.placeholder );
			this.input.selectRange( range.from, range.from + encapsulate.placeholder.length );
		}
		this.input.encapsulateContent( encapsulate.pre, encapsulate.post );
	} else {
		this.input.insertContent( insert );
	}
};

/* Registration */

ve.ui.windowFactory.register( ve.ui.MWMathDialog );
