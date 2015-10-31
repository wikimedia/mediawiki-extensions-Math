/*!
 * VisualEditor UserInterface MWMathInspectorTool class.
 *
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/*global ve, OO */

/**
 * MediaWiki UserInterface math tool.
 *
 * @class
 * @extends ve.ui.InspectorTool
 * @constructor
 * @param {OO.ui.ToolGroup} toolGroup
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMathInspectorTool = function VeUiMWMathInspectorTool( toolGroup, config ) {
	ve.ui.MWMathInspectorTool.super.call( this, toolGroup, config );
};
OO.inheritClass( ve.ui.MWMathInspectorTool, ve.ui.InspectorTool );
ve.ui.MWMathInspectorTool.static.name = 'math';
ve.ui.MWMathInspectorTool.static.group = 'object';
ve.ui.MWMathInspectorTool.static.icon = 'math';
ve.ui.MWMathInspectorTool.static.title = OO.ui.deferMsg(
	'math-visualeditor-mwmathinspector-title' );
ve.ui.MWMathInspectorTool.static.modelClasses = [ ve.dm.MWMathNode ];
ve.ui.MWMathInspectorTool.static.commandName = 'math';
ve.ui.toolFactory.register( ve.ui.MWMathInspectorTool );

ve.ui.commandRegistry.register(
	new ve.ui.Command(
		'math', 'window', 'open',
		{ args: [ 'math' ], supportedSelections: [ 'linear' ] }
	)
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'wikitextMath', 'math', '<math', 5 )
);

ve.ui.sequenceRegistry.register(
	new ve.ui.Sequence( 'texMath', 'math', '$$', 2 )
);
