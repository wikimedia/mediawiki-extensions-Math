/*!
 * VisualEditor DataModel MWMathNode class.
 *
 * @copyright 2011-2013 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/*global ve, OO */

/**
 * DataModel MediaWiki math node.
 *
 * @class
 * @extends ve.dm.MWInlineExtensionNode
 *
 * @constructor
 * @param {Object} [element]
 */
ve.dm.MWMathNode = function VeDmMWMathNode() {
	// Parent constructor
	ve.dm.MWInlineExtensionNode.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWMathNode, ve.dm.MWInlineExtensionNode );

/* Static members */

ve.dm.MWMathNode.static.name = 'mwMath';

ve.dm.MWMathNode.static.tagName = 'img';

ve.dm.MWMathNode.static.extensionName = 'math';

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWMathNode );
