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

ve.ui.MWMathDialog.static.autocompleteWordList = [
	'\\AA', '\\aleph', '\\alpha', '\\amalg', '\\And', '\\angle', '\\approx', '\\approxeq',
	'\\ast', '\\asymp', '\\backepsilon', '\\backprime', '\\backsim', '\\backsimeq', '\\barwedge', '\\Bbbk', '\\because', '\\beta',
	'\\beth', '\\between', '\\bigcap', '\\bigcirc', '\\bigcup', '\\bigodot', '\\bigoplus', '\\bigotimes', '\\bigsqcup', '\\bigstar',
	'\\bigtriangledown', '\\bigtriangleup', '\\biguplus', '\\bigvee', '\\bigwedge', '\\blacklozenge', '\\blacksquare', '\\blacktriangle', '\\blacktriangledown', '\\blacktriangleleft',
	'\\blacktriangleright', '\\bot', '\\bowtie', '\\Box', '\\boxdot', '\\boxminus', '\\boxplus', '\\boxtimes', '\\bullet', '\\bumpeq',
	'\\Bumpeq', '\\cap', '\\Cap', '\\cdot', '\\cdots', '\\centerdot', '\\checkmark', '\\chi', '\\circ', '\\circeq',
	'\\circlearrowleft', '\\circlearrowright', '\\circledast', '\\circledcirc', '\\circleddash', '\\circledS', '\\clubsuit', '\\colon', '\\color', '\\complement',
	'\\cong', '\\coprod', '\\cup', '\\Cup', '\\curlyeqprec', '\\curlyeqsucc', '\\curlyvee', '\\curlywedge', '\\curvearrowleft', '\\curvearrowright',
	'\\dagger', '\\daleth', '\\dashv', '\\ddagger', '\\ddots', '\\definecolor', '\\delta', '\\Delta', '\\diagdown', '\\diagup',
	'\\diamond', '\\Diamond', '\\diamondsuit', '\\digamma', '\\displaystyle', '\\div', '\\divideontimes', '\\doteq', '\\doteqdot', '\\dotplus',
	'\\dots', '\\dotsb', '\\dotsc', '\\dotsi', '\\dotsm', '\\dotso', '\\doublebarwedge', '\\downdownarrows', '\\downharpoonleft', '\\downharpoonright',
	'\\ell', '\\emptyset', '\\epsilon', '\\eqcirc', '\\eqsim', '\\eqslantgtr', '\\eqslantless', '\\equiv', '\\eta', '\\eth',
	'\\exists', '\\fallingdotseq', '\\Finv', '\\flat', '\\forall', '\\frown', '\\Game', '\\gamma', '\\Gamma', '\\geq',
	'\\geqq', '\\geqslant', '\\gets', '\\gg', '\\ggg', '\\gimel', '\\gnapprox', '\\gneq', '\\gneqq', '\\gnsim',
	'\\gtrapprox', '\\gtrdot', '\\gtreqless', '\\gtreqqless', '\\gtrless', '\\gtrsim', '\\gvertneqq', '\\hbar', '\\heartsuit', '\\hline',
	'\\hookleftarrow', '\\hookrightarrow', '\\hslash', '\\iff', '\\iiiint', '\\iiint', '\\iint', '\\Im', '\\imath', '\\implies',
	'\\in', '\\infty', '\\injlim', '\\int', '\\intercal', '\\iota', '\\jmath', '\\kappa', '\\lambda', '\\Lambda',
	'\\land', '\\ldots', '\\leftarrow', '\\Leftarrow', '\\leftarrowtail', '\\leftharpoondown', '\\leftharpoonup', '\\leftleftarrows', '\\leftrightarrow', '\\Leftrightarrow',
	'\\leftrightarrows', '\\leftrightharpoons', '\\leftrightsquigarrow', '\\leftthreetimes', '\\leq', '\\leqq', '\\leqslant', '\\lessapprox', '\\lessdot', '\\lesseqgtr',
	'\\lesseqqgtr', '\\lessgtr', '\\lesssim', '\\limits', '\\ll', '\\Lleftarrow', '\\lll', '\\lnapprox', '\\lneq', '\\lneqq',
	'\\lnot', '\\lnsim', '\\longleftarrow', '\\Longleftarrow', '\\longleftrightarrow', '\\Longleftrightarrow', '\\longmapsto', '\\longrightarrow', '\\Longrightarrow', '\\looparrowleft',
	'\\looparrowright', '\\lor', '\\lozenge', '\\Lsh', '\\ltimes', '\\lVert', '\\lvertneqq', '\\mapsto', '\\measuredangle', '\\mho',
	'\\mid', '\\mod', '\\models', '\\mp', '\\mu', '\\multimap', '\\nabla', '\\natural', '\\ncong', '\\nearrow',
	'\\neg', '\\neq', '\\nexists', '\\ngeq', '\\ngeqq', '\\ngeqslant', '\\ngtr', '\\ni', '\\nleftarrow', '\\nLeftarrow',
	'\\nleftrightarrow', '\\nLeftrightarrow', '\\nleq', '\\nleqq', '\\nleqslant', '\\nless', '\\nmid', '\\nolimits', '\\not', '\\notin',
	'\\nparallel', '\\nprec', '\\npreceq', '\\nrightarrow', '\\nRightarrow', '\\nshortmid', '\\nshortparallel', '\\nsim', '\\nsubseteq', '\\nsubseteqq',
	'\\nsucc', '\\nsucceq', '\\nsupseteq', '\\nsupseteqq', '\\ntriangleleft', '\\ntrianglelefteq', '\\ntriangleright', '\\ntrianglerighteq', '\\nu', '\\nvdash',
	'\\nVdash', '\\nvDash', '\\nVDash', '\\nwarrow', '\\odot', '\\oint', '\\omega', '\\Omega', '\\ominus', '\\oplus',
	'\\oslash', '\\otimes', '\\overbrace', '\\overleftarrow', '\\overleftrightarrow', '\\overline', '\\overrightarrow', '\\P', '\\pagecolor', '\\parallel',
	'\\partial', '\\perp', '\\phi', '\\Phi', '\\pi', '\\Pi', '\\pitchfork', '\\pm', '\\prec', '\\precapprox',
	'\\preccurlyeq', '\\preceq', '\\precnapprox', '\\precneqq', '\\precnsim', '\\precsim', '\\prime', '\\prod', '\\projlim', '\\propto',
	'\\psi', '\\Psi', '\\qquad', '\\quad', '\\Re', '\\rho', '\\rightarrow', '\\Rightarrow', '\\rightarrowtail', '\\rightharpoondown',
	'\\rightharpoonup', '\\rightleftarrows', '\\rightrightarrows', '\\rightsquigarrow', '\\rightthreetimes', '\\risingdotseq', '\\Rrightarrow', '\\Rsh', '\\rtimes', '\\rVert',
	'\\S', '\\scriptscriptstyle', '\\scriptstyle', '\\searrow', '\\setminus', '\\sharp', '\\shortmid', '\\shortparallel', '\\sigma', '\\Sigma',
	'\\sim', '\\simeq', '\\smallfrown', '\\smallsetminus', '\\smallsmile', '\\smile', '\\spadesuit', '\\sphericalangle', '\\sqcap', '\\sqcup',
	'\\sqsubset', '\\sqsubseteq', '\\sqsupset', '\\sqsupseteq', '\\square', '\\star', '\\subset', '\\Subset', '\\subseteq', '\\subseteqq',
	'\\subsetneq', '\\subsetneqq', '\\succ', '\\succapprox', '\\succcurlyeq', '\\succeq', '\\succnapprox', '\\succneqq', '\\succnsim', '\\succsim',
	'\\sum', '\\supset', '\\Supset', '\\supseteq', '\\supseteqq', '\\supsetneq', '\\supsetneqq', '\\surd', '\\swarrow', '\\tau',
	'\\textstyle', '\\textvisiblespace', '\\therefore', '\\theta', '\\Theta', '\\thickapprox', '\\thicksim', '\\times', '\\to', '\\top',
	'\\triangle', '\\triangledown', '\\triangleleft', '\\trianglelefteq', '\\triangleq', '\\triangleright', '\\trianglerighteq', '\\underbrace', '\\underline', '\\upharpoonleft',
	'\\upharpoonright', '\\uplus', '\\upsilon', '\\Upsilon', '\\upuparrows', '\\varepsilon', '\\varinjlim', '\\varkappa', '\\varliminf', '\\varlimsup',
	'\\varnothing', '\\varphi', '\\varpi', '\\varprojlim', '\\varpropto', '\\varrho', '\\varsigma', '\\varsubsetneq', '\\varsubsetneqq', '\\varsupsetneq',
	'\\varsupsetneqq', '\\vartheta', '\\vartriangle', '\\vartriangleleft', '\\vartriangleright', '\\vdash', '\\Vdash', '\\vDash', '\\vdots', '\\vee',
	'\\veebar', '\\vline', '\\Vvdash', '\\wedge', '\\widehat', '\\widetilde', '\\wp', '\\wr', '\\xi', '\\Xi',
	'\\zeta', '\\big', '\\Big', '\\bigg', '\\Bigg', '\\biggl', '\\Biggl', '\\biggr',
	'\\Biggr', '\\bigl', '\\Bigl', '\\bigr', '\\Bigr', '\\backslash', '\\downarrow', '\\Downarrow',
	'\\langle', '\\lbrace', '\\lceil', '\\lfloor', '\\llcorner', '\\lrcorner', '\\rangle', '\\rbrace', '\\rceil', '\\rfloor',
	'\\rightleftharpoons', '\\twoheadleftarrow', '\\twoheadrightarrow', '\\ulcorner', '\\uparrow', '\\Uparrow', '\\updownarrow', '\\Updownarrow', '\\urcorner', '\\Vert',
	'\\vert', '\\lbrack', '\\rbrack', '\\acute', '\\bar', '\\bcancel', '\\bmod', '\\boldsymbol',
	'\\breve', '\\cancel', '\\check', '\\ddot', '\\dot', '\\emph', '\\grave', '\\hat', '\\mathbb', '\\mathbf',
	'\\mathbin', '\\mathcal', '\\mathclose', '\\mathfrak', '\\mathit', '\\mathop', '\\mathopen', '\\mathord', '\\mathpunct', '\\mathrel',
	'\\mathrm', '\\mathsf', '\\mathtt', '\\operatorname', '\\pmod', '\\sqrt', '\\textbf', '\\textit', '\\textrm', '\\textsf',
	'\\texttt', '\\tilde', '\\vec', '\\xcancel', '\\xleftarrow', '\\xrightarrow', '\\binom', '\\cancelto',
	'\\cfrac', '\\dbinom', '\\dfrac', '\\frac', '\\overset', '\\stackrel', '\\tbinom', '\\tfrac', '\\underset',
	'\\atop', '\\choose', '\\over', '\\Coppa', '\\coppa', '\\Digamma', '\\euro', '\\geneuro',
	'\\geneuronarrow', '\\geneurowide', '\\Koppa', '\\koppa', '\\officialeuro', '\\Sampi', '\\sampi', '\\Stigma', '\\stigma', '\\varstigma',
	'\\darr', '\\dArr', '\\Darr', '\\lang', '\\rang', '\\uarr', '\\uArr',
	'\\Uarr', '\\Bbb', '\\bold', '\\alef', '\\alefsym', '\\Alpha', '\\and', '\\ang',
	'\\Beta', '\\bull', '\\Chi', '\\clubs', '\\cnums', '\\Complex', '\\Dagger', '\\diamonds', '\\Doteq', '\\doublecap',
	'\\doublecup', '\\empty', '\\Epsilon', '\\Eta', '\\exist', '\\ge', '\\gggtr', '\\hAar', '\\harr', '\\Harr',
	'\\hearts', '\\image', '\\infin', '\\Iota', '\\isin', '\\Kappa', '\\larr', '\\Larr', '\\lArr', '\\le',
	'\\lrarr', '\\Lrarr', '\\lrArr', '\\Mu', '\\natnums', '\\ne', '\\Nu', '\\O', '\\omicron', '\\Omicron',
	'\\or', '\\part', '\\plusmn', '\\rarr', '\\Rarr', '\\rArr', '\\real', '\\reals', '\\Reals', '\\restriction',
	'\\Rho', '\\sdot', '\\sect', '\\spades', '\\sub', '\\sube', '\\supe', '\\Tau', '\\thetasym', '\\varcoppa',
	'\\weierp', '\\Zeta', '\\C', '\\H', '\\N', '\\Q', '\\R', '\\Z', '\\arccos',
	'\\arcsin', '\\arctan', '\\arg', '\\cos', '\\cosh', '\\cot', '\\coth', '\\csc', '\\deg', '\\det',
	'\\dim', '\\exp', '\\gcd', '\\hom', '\\inf', '\\ker', '\\lg', '\\lim', '\\liminf', '\\limsup',
	'\\ln', '\\log', '\\max', '\\min', '\\Pr', '\\sec', '\\sin', '\\sinh', '\\sup', '\\tan',
	'\\tanh', '\\arccot', '\\arcsec', '\\arccsc', '\\sgn', '\\sen'
];

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
		autocomplete: 'live',
		autocompleteWordList: ve.ui.MWMathDialog.static.autocompleteWordList
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
		}, OO.ui.theme.getDialogTransitionDuration() );
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
