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
	ve.ui.MWExtensionInspector.call( this, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathInspector, ve.ui.MWLiveExtensionInspector );

/* Static properties */

ve.ui.MWMathInspector.static.name = 'math';

ve.ui.MWMathInspector.static.icon = 'math';

ve.ui.MWMathInspector.static.title = OO.ui.deferMsg( 'math-visualeditor-mwmathinspector-title' );

ve.ui.MWMathInspector.static.nodeView = ve.ce.MWMathNode;

ve.ui.MWMathInspector.static.nodeModel = ve.dm.MWMathNode;

ve.ui.MWMathInspector.static.forcedLtr = true;

ve.ui.MWMathInspector.static.dummyAttributes = {
        'name': 'math',
        'attrs': {},
        'body': {
            'extsrc': ''
        }
    };

/* Registration */

ve.ui.inspectorFactory.register( ve.ui.MWMathInspector );
