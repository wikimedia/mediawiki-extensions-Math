/*!
 * VisualEditor DataModel MWMathNode class.
 *
 * @copyright 2011-2015 VisualEditor Team and others; see AUTHORS.txt
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
	ve.dm.MWMathNode.super.apply( this, arguments );
};

/* Inheritance */

OO.inheritClass( ve.dm.MWMathNode, ve.dm.MWInlineExtensionNode );

/* Static members */

ve.dm.MWMathNode.static.name = 'mwMath';

ve.dm.MWMathNode.static.tagName = 'img';

ve.dm.MWMathNode.static.extensionName = 'math';

/* Static Methods */

/**
 * @inheritdoc ve.dm.GeneratedContentNode
 */
ve.dm.MWMathNode.static.getHashObjectForRendering = function ( dataElement ) {
	// Parent method
	var hashObject = ve.dm.MWMathNode.super.static.getHashObjectForRendering.call( this, dataElement );

	// The id does not affect the rendering.
	if ( hashObject.mw.attrs ) {
		delete hashObject.mw.attrs.id;
	}
	return hashObject;
};

/* Registration */

ve.dm.modelRegistry.register( ve.dm.MWMathNode );
