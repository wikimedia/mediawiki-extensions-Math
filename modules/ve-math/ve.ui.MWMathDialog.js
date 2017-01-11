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
	'AA', 'acute', 'alef', 'alefsym', 'aleph', 'alpha', 'Alpha', 'amalg', 'And',
	'and', 'ang', 'angle', 'approx', 'approxeq', 'arccos', 'arccot', 'arccsc', 'arcsec', 'arcsin',
	'arctan', 'arg', 'ast', 'asymp', 'atop', 'backepsilon', 'backprime', 'backsim', 'backsimeq', 'backslash',
	'bar', 'barwedge', 'Bbb', 'Bbbk', 'bcancel', 'because', 'beta', 'Beta', 'beth', 'between',
	'big', 'Big', 'bigcap', 'bigcirc', 'bigcup', 'bigg', 'Bigg', 'biggl', 'Biggl', 'biggr',
	'Biggr', 'bigl', 'Bigl', 'bigodot', 'bigoplus', 'bigotimes', 'bigr', 'Bigr', 'bigsqcup', 'bigstar',
	'bigtriangledown', 'bigtriangleup', 'biguplus', 'bigvee', 'bigwedge', 'binom', 'blacklozenge', 'blacksquare', 'blacktriangle', 'blacktriangledown',
	'blacktriangleleft', 'blacktriangleright', 'bmod', 'bold', 'boldsymbol', 'bot', 'bowtie', 'Box', 'boxdot', 'boxminus',
	'boxplus', 'boxtimes', 'breve', 'bull', 'bullet', 'bumpeq', 'Bumpeq', 'C', 'cancel', 'cancelto',
	'cap', 'Cap', 'cdot', 'cdots', 'centerdot', 'cfrac', 'check', 'checkmark', 'chi', 'Chi',
	'choose', 'circ', 'circeq', 'circlearrowleft', 'circlearrowright', 'circledast', 'circledcirc', 'circleddash', 'circledS', 'clubs',
	'clubsuit', 'cnums', 'colon', 'color', 'complement', 'Complex', 'cong', 'Coppa', 'coppa', 'coprod',
	'cos', 'cosh', 'cot', 'coth', 'csc', 'cup', 'Cup', 'curlyeqprec', 'curlyeqsucc', 'curlyvee',
	'curlywedge', 'curvearrowleft', 'curvearrowright', 'dagger', 'Dagger', 'daleth', 'darr', 'dArr', 'Darr', 'dashv',
	'dbinom', 'ddagger', 'ddot', 'ddots', 'definecolor', 'deg', 'delta', 'Delta', 'det', 'dfrac',
	'diagdown', 'diagup', 'diamond', 'Diamond', 'diamonds', 'diamondsuit', 'digamma', 'Digamma', 'dim', 'displaystyle',
	'div', 'divideontimes', 'dot', 'doteq', 'Doteq', 'doteqdot', 'dotplus', 'dots', 'dotsb', 'dotsc',
	'dotsi', 'dotsm', 'dotso', 'doublebarwedge', 'doublecap', 'doublecup', 'downarrow', 'Downarrow', 'downdownarrows', 'downharpoonleft',
	'downharpoonright', 'ell', 'emph', 'empty', 'emptyset', 'epsilon', 'Epsilon', 'eqcirc', 'eqsim', 'eqslantgtr',
	'eqslantless', 'equiv', 'eta', 'Eta', 'eth', 'euro', 'exist', 'exists', 'exp', 'fallingdotseq',
	'Finv', 'flat', 'forall', 'frac', 'frown', 'Game', 'gamma', 'Gamma', 'gcd', 'ge',
	'geneuro', 'geneuronarrow', 'geneurowide', 'geq', 'geqq', 'geqslant', 'gets', 'gg', 'ggg', 'gggtr',
	'gimel', 'gnapprox', 'gneq', 'gneqq', 'gnsim', 'grave', 'gtrapprox', 'gtrdot', 'gtreqless', 'gtreqqless',
	'gtrless', 'gtrsim', 'gvertneqq', 'H', 'hAar', 'harr', 'Harr', 'hat', 'hbar', 'hearts',
	'heartsuit', 'hline', 'hom', 'hookleftarrow', 'hookrightarrow', 'hslash', 'iff', 'iiiint', 'iiint', 'iint',
	'Im', 'image', 'imath', 'implies', 'in', 'inf', 'infin', 'infty', 'injlim', 'int',
	'intercal', 'iota', 'Iota', 'isin', 'jmath', 'kappa', 'Kappa', 'ker', 'Koppa', 'koppa',
	'lambda', 'Lambda', 'land', 'lang', 'langle', 'larr', 'Larr', 'lArr', 'lbrace', 'lbrack',
	'lceil', 'ldots', 'le', 'leftarrow', 'Leftarrow', 'leftarrowtail', 'leftharpoondown', 'leftharpoonup', 'leftleftarrows', 'leftrightarrow',
	'Leftrightarrow', 'leftrightarrows', 'leftrightharpoons', 'leftrightsquigarrow', 'leftthreetimes', 'leq', 'leqq', 'leqslant', 'lessapprox', 'lessdot',
	'lesseqgtr', 'lesseqqgtr', 'lessgtr', 'lesssim', 'lfloor', 'lg', 'lim', 'liminf', 'limits', 'limsup',
	'll', 'llcorner', 'Lleftarrow', 'lll', 'ln', 'lnapprox', 'lneq', 'lneqq', 'lnot', 'lnsim',
	'log', 'longleftarrow', 'Longleftarrow', 'longleftrightarrow', 'Longleftrightarrow', 'longmapsto', 'longrightarrow', 'Longrightarrow', 'looparrowleft', 'looparrowright',
	'lor', 'lozenge', 'lrarr', 'Lrarr', 'lrArr', 'lrcorner', 'Lsh', 'ltimes', 'lVert', 'lvertneqq',
	'mapsto', 'mathbb', 'mathbf', 'mathbin', 'mathcal', 'mathclose', 'mathfrak', 'mathit', 'mathop', 'mathopen',
	'mathord', 'mathpunct', 'mathrel', 'mathrm', 'mathsf', 'mathtt', 'max', 'measuredangle', 'mho', 'mid',
	'min', 'mod', 'models', 'mp', 'mu', 'Mu', 'multimap', 'N', 'nabla', 'natnums',
	'natural', 'ncong', 'ne', 'nearrow', 'neg', 'neq', 'nexists', 'ngeq', 'ngeqq', 'ngeqslant',
	'ngtr', 'ni', 'nleftarrow', 'nLeftarrow', 'nleftrightarrow', 'nLeftrightarrow', 'nleq', 'nleqq', 'nleqslant', 'nless',
	'nmid', 'nolimits', 'not', 'notin', 'nparallel', 'nprec', 'npreceq', 'nrightarrow', 'nRightarrow', 'nshortmid',
	'nshortparallel', 'nsim', 'nsubseteq', 'nsubseteqq', 'nsucc', 'nsucceq', 'nsupseteq', 'nsupseteqq', 'ntriangleleft', 'ntrianglelefteq',
	'ntriangleright', 'ntrianglerighteq', 'nu', 'Nu', 'nvdash', 'nVdash', 'nvDash', 'nVDash', 'nwarrow', 'O',
	'odot', 'officialeuro', 'oint', 'omega', 'Omega', 'omicron', 'Omicron', 'ominus', 'operatorname', 'oplus',
	'or', 'oslash', 'otimes', 'over', 'overbrace', 'overleftarrow', 'overleftrightarrow', 'overline', 'overrightarrow', 'overset',
	'P', 'pagecolor', 'parallel', 'part', 'partial', 'perp', 'phi', 'Phi', 'pi', 'Pi',
	'pitchfork', 'plusmn', 'pm', 'pmod', 'Pr', 'prec', 'precapprox', 'preccurlyeq', 'preceq', 'precnapprox',
	'precneqq', 'precnsim', 'precsim', 'prime', 'prod', 'projlim', 'propto', 'psi', 'Psi', 'Q',
	'qquad', 'quad', 'R', 'rang', 'rangle', 'rarr', 'Rarr', 'rArr', 'rbrace', 'rbrack',
	'rceil', 'Re', 'real', 'reals', 'Reals', 'restriction', 'rfloor', 'rho', 'Rho', 'rightarrow',
	'Rightarrow', 'rightarrowtail', 'rightharpoondown', 'rightharpoonup', 'rightleftarrows', 'rightleftharpoons', 'rightrightarrows', 'rightsquigarrow', 'rightthreetimes', 'risingdotseq',
	'Rrightarrow', 'Rsh', 'rtimes', 'rVert', 'S', 'Sampi', 'sampi', 'scriptscriptstyle', 'scriptstyle', 'sdot',
	'searrow', 'sec', 'sect', 'sen', 'setminus', 'sgn', 'sharp', 'shortmid', 'shortparallel', 'sigma',
	'Sigma', 'sim', 'simeq', 'sin', 'sinh', 'smallfrown', 'smallsetminus', 'smallsmile', 'smile', 'spades',
	'spadesuit', 'sphericalangle', 'sqcap', 'sqcup', 'sqrt', 'sqsubset', 'sqsubseteq', 'sqsupset', 'sqsupseteq', 'square',
	'stackrel', 'star', 'Stigma', 'stigma', 'sub', 'sube', 'subset', 'Subset', 'subseteq', 'subseteqq',
	'subsetneq', 'subsetneqq', 'succ', 'succapprox', 'succcurlyeq', 'succeq', 'succnapprox', 'succneqq', 'succnsim', 'succsim',
	'sum', 'sup', 'supe', 'supset', 'Supset', 'supseteq', 'supseteqq', 'supsetneq', 'supsetneqq', 'surd',
	'swarrow', 'tan', 'tanh', 'tau', 'Tau', 'tbinom', 'textbf', 'textit', 'textrm', 'textsf',
	'textstyle', 'texttt', 'textvisiblespace', 'tfrac', 'therefore', 'theta', 'Theta', 'thetasym', 'thickapprox', 'thicksim',
	'tilde', 'times', 'to', 'top', 'triangle', 'triangledown', 'triangleleft', 'trianglelefteq', 'triangleq', 'triangleright',
	'trianglerighteq', 'twoheadleftarrow', 'twoheadrightarrow', 'uarr', 'uArr', 'Uarr', 'ulcorner', 'underbrace', 'underline', 'underset',
	'uparrow', 'Uparrow', 'updownarrow', 'Updownarrow', 'upharpoonleft', 'upharpoonright', 'uplus', 'upsilon', 'Upsilon', 'upuparrows',
	'urcorner', 'varcoppa', 'varepsilon', 'varinjlim', 'varkappa', 'varliminf', 'varlimsup', 'varnothing', 'varphi', 'varpi',
	'varprojlim', 'varpropto', 'varrho', 'varsigma', 'varstigma', 'varsubsetneq', 'varsubsetneqq', 'varsupsetneq', 'varsupsetneqq', 'vartheta',
	'vartriangle', 'vartriangleleft', 'vartriangleright', 'vdash', 'Vdash', 'vDash', 'vdots', 'vec', 'vee', 'veebar',
	'Vert', 'vert', 'vline', 'Vvdash', 'wedge', 'weierp', 'widehat', 'widetilde', 'wp', 'wr',
	'xcancel', 'xi', 'Xi', 'xleftarrow', 'xrightarrow', 'Z', 'zeta', 'Zeta'
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
