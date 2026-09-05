const extensionAssetsPath = mw.config.get( 'wgExtensionAssetsPath' );
const { mmlFilter } = require( './ext.math.mathjax.mml.js' );

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
		mmlFilters: [ mmlFilter ],
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
		// Override MathML input or SVG output logic.
		//
		// This is called after the components (listed in MathJax.loader.load) are downloaded.
		ready() {
			// MathJax creates anchor tags from MathML elements with href attributes.
			// But it does not add the title attributes from these elements
			// that we need for the extension Popups
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

			// Implement MathJax.options.ignoreHtmlClass for MathML input (previously "ignoreClass")
			// from https://github.com/mathjax/MathJax/issues/2770#issuecomment-920428602
			const { FindMathML } = window.MathJax._.input.mathml.FindMathML;
			const { combineDefaults } = window.MathJax._.components.global;
			class MyFindMathML extends FindMathML {
				processMath( set ) {
					const adaptor = this.adaptor;
					for ( const node of set.values() ) {
						if ( adaptor.hasClass( node, 'mathjax_ignore' ) ) {
							set.delete( node );
						} else {
							// T436026: Preserve original MathML for screen reader a11y
							const a11ySpan = document.createElement( 'span' );
							a11ySpan.className = 'mwe-math-mathml-a11y';
							a11ySpan.style.cssText = 'display: none;';
							const a11yMath = node.cloneNode( true );
							a11yMath.classList.add( 'mathjax_ignore' );
							a11ySpan.appendChild( a11yMath );
							node.before( a11ySpan );
						}
					}
					return super.processMath( set );
				}
			}

			combineDefaults( window.MathJax.config, 'mml', { FindMathML: new MyFindMathML() } );

			// This eventually calls window.MathJax.pageReady(), which we override below
			window.MathJax.startup.defaultReady();
		},

		// Override when and how we typeset.
		//
		// This is called after window.MathJax.ready().
		async pageReady() {
			// See https://phabricator.wikimedia.org/T375932 and the suggested fix from
			// https://github.com/mathjax/MathJax/issues/3292#issuecomment-3487698042
			// Makes rendering of \matcal look similar to the browsers MathML rendering
			// and the old image rendering.
			// Note that \mathsrc (which is unsupported by texvc) would map to the
			// same unicode chars and thus should not be activated.
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

			// This uses FindMathML to find and replace all `<math>` with `<mjx-lazy>` placeholders.
			// It then calls MathJax.typeset (via ui/lazy, using requestIdleCallback, so it happens after
			// and outside the pageReady callstack), which will render them to SVG if/when they are
			// visible in the viewport.
			await window.MathJax.startup.defaultPageReady();

			// Handle dynamically added <math> elements
			//
			// E.g. Live Preview (T434469), VisualEditor post-save (T419356),
			// and DiscussionTools reply preview (T422077).
			//
			// https://docs.mathjax.org/en/latest/advanced/typeset.html#handling-new-content
			mw.hook( 'wikipage.content' ).add( () => {
				// Find and replace new <math> elements with `<mjx-lazy>` placeholders, and
				// register them with ui/lazy to render to SVG if/when in the viewport.
				window.MathJax.typeset();
			} );
		},
		output: 'svg'
	}
};

require( './mathjax/startup.js' );
