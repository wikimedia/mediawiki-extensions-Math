/*!
 * VisualEditor UserInterface MWMathInspector class.
 *
 * @copyright 2011-2013 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/*global ve, OO */

/**
 * MediaWiki math inspector.
 *
 * @class
 * @extends ve.ui.MWExtensionInspector
 *
 * @constructor
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMathInspector = function VeUiMWMathInspector( config ) {
	// Parent constructor
	ve.ui.MWExtensionInspector.call( this, config );

	this.onChangeHandler = ve.debounce( ve.bind( this.updatePreview, this ), 250 );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathInspector, ve.ui.MWExtensionInspector );

/* Static properties */

ve.ui.MWMathInspector.static.name = 'math';

ve.ui.MWMathInspector.static.icon = 'math';

ve.ui.MWMathInspector.static.title = OO.ui.deferMsg( 'math-visualeditor-mwmathinspector-title' );

ve.ui.MWMathInspector.static.nodeView = ve.ce.MWMathNode;

ve.ui.MWMathInspector.static.nodeModel = ve.dm.MWMathNode;

/* Methods */

/**
 * Update the math node rendering to reflect the content entered into the inspector.
 */
ve.ui.MWMathInspector.prototype.updatePreview = function () {
	var mwData = ve.copy( this.node.getAttribute( 'mw' ) ),
		newsrc = this.input.getValue();

	mwData.body.extsrc = newsrc;

	if ( this.visible ) {
		this.getFragment().changeAttributes( { 'mw': mwData } );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWMathInspector.prototype.setup = function ( data ) {
	// Parent method
	ve.ui.MWExtensionInspector.prototype.setup.call( this, data );

	this.dontClose = true;

	this.getFragment().getSurface().pushStaging();

	var mwData;

	this.node = this.getFragment().getSelectedNode();
	if ( !this.node || !( this.node instanceof ve.dm.MWMathNode ) ) {
		// Create a dummy node, needed for live preview
		mwData = {
			'name': 'math',
			'attrs': {},
			'body': {
				'extsrc': ''
			}
		};
		this.getFragment().collapseRangeToEnd().insertContent( [
			{
				'type': 'mwMath',
				'attributes': {
					'mw': mwData
				}
			},
			{ 'type': '/mwMath' }
		] );
		this.node = this.getFragment().getSelectedNode();
	}

	this.input.on( 'change', this.onChangeHandler );

	// Override directionality settings, inspector's input
	// should always be LTR:
	this.input.setRTL( false );

	this.dontClose = false;
};

ve.ui.MWMathInspector.prototype.close = function () {
	// HACK ignore close() calls while setting up
	// This works around the fact that inserting a focusable node
	// causes a focus event in 1.23wmf22 but not in 1.24wmf1
	if ( !this.dontClose ) {
		// Parent method
		ve.ui.MWExtensionInspector.prototype.close.apply( this, arguments );
	}
};

/**
 * @inheritdoc
 */
ve.ui.MWMathInspector.prototype.teardown = function ( data ) {
	var newsrc = this.input.getValue(),
		surfaceModel = this.getFragment().getSurface();

	this.input.off( 'change', this.onChangeHandler );

	this.getFragment().getSurface().applyStaging();

	if ( newsrc === '' ) {
		// The user tried to empty the node, remove it
		surfaceModel.change( ve.dm.Transaction.newFromRemoval(
			surfaceModel.getDocument(), this.node.getOuterRange()
		) );
	}
	// Grandparent method; we're overriding the parent behavior with applyStaging
	ve.ui.Inspector.prototype.teardown.call( this, data );
};

/* Registration */

ve.ui.inspectorFactory.register( ve.ui.MWMathInspector );
