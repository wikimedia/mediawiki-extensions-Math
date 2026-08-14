const extensionAssetsPath = mw.config.get( 'wgExtensionAssetsPath' );

// helper function for https://phabricator.wikimedia.org/T375932
function remapChars( v1, v2, base, map, font ) {
	const c1 = v1.chars;
	const c2 = v2.chars;
	for ( let i = 0; i < 26; i++ ) {
		const data1 = c1[ map[ i ] || base + i ] || [];
		const data2 = c2[ 0x41 + i ];
		if ( data1.length === 0 ) {
			c1[ base + i ] = data1;
		}
		for ( const j of [ 0, 1, 2 ] ) {
			data1[ j ] = data2[ j ];
		}
		data1[ 3 ] = Object.assign( {}, data2[ 3 ], {
			f: font,
			c: String.fromCharCode( 0x41 + i )
		} );
	}
}

window.MathJax = {
	loader: {
		// see https://docs.mathjax.org/en/latest/input/mathml.html
		load: [
			// allow MathML input
			'input/mml',
			// render only when visible
			'ui/lazy',
			// output as SVG to look like the mathoid output
			'output/svg'
		],
		// see https://docs.mathjax.org/en/latest/options/startup/loader.html
		paths: {
			mathjax: extensionAssetsPath + '/Math/modules/mathjax',
			fonts: extensionAssetsPath + '/Math/modules'
		}
	},
	mml: {
		// allow links
		allowHtmlInTokenNodes: true,
		mmlFilters: [
			// from https://github.com/mathjax/MathJax/issues/3540
			( { data } ) => {
				const mtables = data.querySelectorAll( 'mtable.mwe-math-smallmatrix' );
				for ( const mtable of Array.from( mtables ) ) {
					mtable.setAttribute( 'data-mjx-smallmatrix', 'true' );
					mtable.setAttribute( 'rowspacing', '.2em' );
					mtable.setAttribute( 'columnspacing', '0.333em' );
					const mstyle = document.createElementNS( 'http://www.w3.org/1998/Math/MathML', 'mstyle' );
					mstyle.setAttribute( 'scriptlevel', '1' );
					mtable.replaceWith( mstyle );
					mstyle.appendChild( mtable );
				}
			}
		],
		postFilters: [
			( { data } ) => {
				data.walkTree( ( node ) => {
					if ( node.isKind( 'mtd' ) && node.attributes.isSet( 'class' ) ) {
						for ( const side of [ 'l', 'r' ] ) {
							const key = 'mwe-math-columnalign-' + side;
							const classes = node.attributes.get( 'class' ).split( /\s+/ );
							if ( classes.includes( key ) ) {
								classes.splice( classes.indexOf( key ), 1 );
								if ( classes.length ) {
									node.attributes.set( 'class', classes.join( '' ) );
								} else {
									node.attributes.unset( 'class' );
								}
								node.attributes.set( 'columnalign', { l: 'left', r: 'right' }[ side ] );
								break;
							}
						}
					}
				} );
			}
		]
	},
	startup: {
		// MathJax creates anchor tags from MathML elements with href attributes.
		// But it does not add the title attributes from these elements
		// that we need for the extension Popups
		ready() {
			const { MML } = window.MathJax._.core.MmlTree.MML;
			MML.a = MML.mrow;
			const { SvgWrapper } = window.MathJax._.output.svg.Wrapper;
			const handleHref = SvgWrapper.prototype.handleHref;
			SvgWrapper.prototype.handleHref = function ( parents ) {
				const attributes = this.node.attributes;
				if ( !attributes.getExplicit( 'href' ) ) {
					return parents;
				}
				const anchors = handleHref.call( this, parents );
				const title = attributes.getExplicit( 'title' );
				if ( title ) {
					for ( const anchor of anchors ) {
						this.adaptor.setAttribute( anchor, 'title', title );
					}
				}
				return anchors;
			};
			const { FindMathML } = window.MathJax._.input.mathml.FindMathML;
			const { combineDefaults } = window.MathJax._.components.global;
			// from https://github.com/mathjax/MathJax/issues/2770#issuecomment-920428602
			class MyFindMathML extends FindMathML {
				processMath( set ) {
					const adaptor = this.adaptor;
					for ( const node of set.values() ) {
						if ( adaptor.hasClass( node, 'mathjax_ignore' ) ) {
							set.delete( node );
						}
					}
					return super.processMath( set );
				}
			}
			combineDefaults( window.MathJax.config, 'mml', { FindMathML: new MyFindMathML() } );
			window.MathJax.startup.defaultReady();
		},
		// See https://phabricator.wikimedia.org/T375932 and the suggested fix from
		// https://github.com/mathjax/MathJax/issues/3292#issuecomment-3487698042
		// Makes rendering of \matcal look similar to the browsers MathML rendering
		// and the old image rendering.
		// Note that \mathsrc (which is unsupported by texvc) would map to the
		// same unicode chars and thus should not be activated.
		async pageReady() {
			const font = window.MathJax.startup.document.outputJax.font;
			Object.assign( font, {
				fontLoadDynamicFile: font.loadDynamicFile,
				async loadDynamicFile( dynamic ) {
					await this.fontLoadDynamicFile( dynamic );
					if ( dynamic.file === 'script' ) {
						await this.fontLoadDynamicFile( this.constructor.dynamicFiles.calligraphic );
						const variant = font.variant;
						const map = { 1: 0x212C, 4: 0x2130, 5: 0x2131, 7: 0x210B, 8: 0x2110, 11: 0x2112, 12: 0x2133, 17: 0x211B };
						remapChars( variant.normal, variant[ '-tex-calligraphic' ], 0x1D49C, map, 'C' );
						remapChars( variant.normal, variant[ '-tex-bold-calligraphic' ], 0x1D4D0, {}, 'CB' );
					}
				}
			} );
			await window.MathJax.startup.defaultPageReady();

			// Handle dynamically added <math> elements
			//
			// E.g. Live Preview (T434469), VisualEditor post-save (T419356),
			// and DiscussionTools reply preview (T422077).
			//
			// https://docs.mathjax.org/en/latest/advanced/typeset.html#handling-new-content
			mw.hook( 'wikipage.content' ).add( () => {
				window.MathJax.typeset();
			} );
		},
		output: 'svg'
	}
};
