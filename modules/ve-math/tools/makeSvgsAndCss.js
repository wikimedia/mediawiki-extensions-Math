#!/usr/bin/env node

/* eslint-disable no-console, no-use-before-define */

( function () {
	function generateCSS( symbolsFile, cssFile, inputType ) {
		var i, count, currentClassName, group, symbol, symbols, symbolObject,
			symbolsData, cssData, cssLines, alignBaseline,
			unmodifiedClasses = {},
			cssRules = [], // Whole CSS rules
			cssClasses = {}, // Unique part of class name and whether baseline is shifted
			currentRule = [],
			symbolList = [], // Symbols whose CSS rules need to be added or adjusted
			cssPrefix = '.ve-ui-mwLatexSymbol-',
			fs = require( 'fs' ),
			http = require( 'http' ),
			querystring = require( 'querystring' ),
			mathoidMaxConnections = 20,
			// If symbol.alignBaseline is true, a background-position property will be added to the
			// CSS rule to shift the baseline of the SVG to be a certain proportion of the way up the
			// button.
			singleButtonHeight = 1.8, // Height of the single-height math dialog buttons in em
			baseline = 0.65; // Proportion of the way down the button the baseline should be

		symbolsData = fs.readFileSync( symbolsFile ).toString();
		try {
			cssData = fs.readFileSync( cssFile ).toString();
		} catch ( e ) {}

		function encodeURIComponentForCSS( str ) {
			return encodeURIComponent( str )
				.replace( /[!'*()]/g, function ( chr ) {
					return '%' + chr.charCodeAt( 0 ).toString( 16 );
				} );
		}

		/**
		 * Make the className, replacing any non-alphanumerics with their character code
		 *
		 * The reverse of function would look like this, although we have no use for it yet:
		 *
		 *  return className.replace( /_([0-9]+)_/g, function () {
		 *    return String.fromCharCode( +arguments[ 1 ] );
		 *  } );
		 *
		 * @param {string} tex TeX input
		 * @return {string} Class name
		 */
		function texToClass( tex ) {
			return tex.replace( /[^\w]/g, function ( c ) {
				return '_' + c.charCodeAt( 0 ) + '_';
			} );
		}

		function makeRequest( symbol ) {
			var request,
				tex = symbol.tex || symbol.insert,
				data = querystring.stringify( {
					q: inputType === 'chem' ? '\\ce{' + tex + '}' : tex,
					type: inputType
				} ),
				// API call to mathoid
				options = {
					host: 'localhost',
					port: '10044',
					path: '/',
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
						'Content-Length': Buffer.byteLength( data )
					}
				};
			// Populate and make the API call
			request = http.request( options, function ( res ) {
				var body = '';
				res.setEncoding( 'utf8' );

				res.on( 'data', function ( data ) {
					body += data;
				} );

				res.on( 'end', function () {
					var cssRule, buttonHeight, height, verticalAlign, heightDifference, offset,
						className = texToClass( tex ),
						data = JSON.parse( body ),
						svg = data.svg;

					if ( !svg ) {
						console.log( tex + ' FAILED: ' + body );
						onEnd();
						return;
					}

					cssRule = cssPrefix + className + ' {\n' +
						'\tbackground-image: url( data:image/svg+xml,' + encodeURIComponentForCSS( svg ) + ' );\n';

					if ( symbol.alignBaseline ) {
						// Convert buttonHeight from em to ex, because SVG height is given in ex. (This is an
						// approximation, since the em:ex ratio differs from font to font.)
						buttonHeight = symbol.largeLayout ? singleButtonHeight * 4 : singleButtonHeight * 1.9931;
						// height and verticalAlign rely on the format of the SVG parameters
						// HACK: Adjust these by a factor of 0.8 to match VE's default font size of 0.8em
						height = parseFloat( data.mathoidStyle.match( /height:\s*([\d.]+)ex/ )[ 1 ] ) * 0.8;
						verticalAlign = -parseFloat( data.mathoidStyle.match( /vertical-align:\s*([\d.]+)ex/ )[ 1 ] ) * 0.8;
						// CSS percentage positioning is based on the difference between the image and container sizes
						heightDifference = buttonHeight - height;
						offset = 100 * ( verticalAlign - height + ( baseline * buttonHeight ) ) / heightDifference;

						cssRule += '\tbackground-position: 50% ' + offset + '%;\n' +
							'}';
						cssRules.push( cssRule );
						console.log( tex + ' -> ' + className );
					} else {
						cssRule += '}';
						cssRules.push( cssRule );
						console.log( tex + ' -> ' + className );
					}
					onEnd();

				} );
			} );
			request.setTimeout( 10000 );
			request.write( data );
			request.end();
			runNext();
		}

		function onEnd() {
			count--;
			runNext();
		}

		function runNext() {
			if ( count < mathoidMaxConnections && symbolList.length ) {
				count++;
				makeRequest( symbolList.shift() );
			}
			if ( !symbolList.length && !count ) {
				cssRules.sort();
				fs.writeFileSync(
					cssFile,
					'/*!\n' +
					' * This file is GENERATED by tools/makeSvgsAndCss.js\n' +
					' * DO NOT EDIT\n' +
					' */\n' +
					'\n' +
					cssRules.join( '\n\n' ) +
					'\n'
				);
			}
		}

		if ( cssData ) {
			cssLines = cssData.split( '\n' );
			for ( i = 0; i < cssLines.length; i++ ) {
				if ( cssLines[ i ].indexOf( cssPrefix ) === 0 ) {
					currentClassName = cssLines[ i ].slice( cssPrefix.length, -2 );
					currentRule.push( cssLines[ i ] );
					cssClasses[ currentClassName ] = false; // Default to false
				} else if ( currentRule.length ) {
					currentRule.push( cssLines[ i ] );
					if ( cssLines[ i ].indexOf( '\tbackground-position' ) === 0 ) {
						cssClasses[ currentClassName ] = true;
					}
					if ( cssLines[ i ].indexOf( '}' ) === 0 ) {
						cssRules.push( currentRule.join( '\n' ) );
						currentRule.splice( 0, currentRule.length );
					}
				}
			}
		}

		symbolObject = JSON.parse( symbolsData );
		for ( group in symbolObject ) {
			symbols = symbolObject[ group ];
			for ( i = 0; i < symbols.length; i++ ) {
				symbol = symbols[ i ];
				if ( symbol.duplicate || symbol.notWorking ) {
					continue;
				}
				currentClassName = texToClass( symbol.tex || symbol.insert );
				alignBaseline = !symbol.alignBaseline;
				// If symbol is not in the old CSS file, or its alignBaseline status has changed,
				// add it to symbolList. Check to make sure it hasn't already been added.
				if ( cssClasses[ currentClassName ] === undefined ||
					( unmodifiedClasses[ currentClassName ] !== true &&
						cssClasses[ currentClassName ] === alignBaseline ) ) {
					symbolList.push( symbol );
				} else {
					// At the end of this loop, any CSS class names that aren't in unmodifiedClasses
					// will be deleted from cssRules. cssRules will then only contain rules that will
					// stay unmodified.
					unmodifiedClasses[ currentClassName ] = true;
				}
			}
		}

		// Keep only classes that will stay the same. Remove classes that are being adjusted and
		// classes of symbols that have been deleted from the JSON.
		cssRules = cssRules.filter( function ( rule ) {
			currentClassName = rule.split( '\n' )[ 0 ].slice( cssPrefix.length, -2 );
			if ( unmodifiedClasses[ currentClassName ] ) {
				return true;
			}
			console.log( 'Removing or adjusting: ' + currentClassName );
			return false;
		} );

		count = 0;
		runNext();
	}

	generateCSS( '../mathSymbols.json', '../ve.ui.MWMathSymbols.css', 'tex' );
	generateCSS( '../chemSymbols.json', '../ve.ui.MWChemSymbols.css', 'chem' );
}() );