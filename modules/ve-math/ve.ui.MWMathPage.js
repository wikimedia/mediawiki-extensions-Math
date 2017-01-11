/*!
 * VisualEditor user interface MWMathPage class.
 *
 * @copyright 2015 VisualEditor Team and others; see AUTHORS.txt
 * @license The MIT License (MIT); see LICENSE.txt
 */

/**
 * Math dialog symbols page
 *
 * @class
 * @extends OO.ui.PageLayout
 *
 * @constructor
 * @param {string} name Unique symbolic name of page
 * @param {Object} [config] Configuration options
 */
ve.ui.MWMathPage = function VeUiMWMathPage( name, config ) {
	// Parent constructor
	ve.ui.MWMathPage.super.call( this, name, config );
};

/* Inheritance */

OO.inheritClass( ve.ui.MWMathPage, ve.ui.MWLatexPage );
