/*!
 * VisualEditor MediaWiki UserInterface math tool class.
 *
 * @copyright 2011-2013 VisualEditor Team and others; see AUTHORS.txt
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
	ve.ui.InspectorTool.call( this, toolGroup, config );
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
	new ve.ui.Command( 'math', 'window', 'open', 'math' )
);
