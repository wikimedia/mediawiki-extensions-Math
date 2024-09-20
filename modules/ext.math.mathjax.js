( function () {
	'use strict';
	const extensionAssetsPath = mw.config.get( 'wgExtensionAssetsPath' );
	window.MathJax = {
		loader: {
			// see https://docs.mathjax.org/en/latest/input/mathml.html
			load: [ '[mml]/mml3' ],
			// see https://docs.mathjax.org/en/latest/options/startup/loader.html
			paths: {
				mathjax: extensionAssetsPath + '/Math/modules/mathjax/es5'
			}
		},
		// See https://phabricator.wikimedia.org/T375241 and the suggested startup function from
		// https://github.com/mathjax/MathJax/issues/3030#issuecomment-1490520850
		// This workaround will be included in the MathJax 4 release and no longer be
		// required when we upgrade to MathJax 4.
		startup: {
			ready() {
				const { Mml3 } = window.MathJax._.input.mathml.mml3.mml3;
				window.MathJax.startup.defaultReady();
				const mml3 = new Mml3( window.MathJax.startup.document );
				const adaptor = window.MathJax.startup.document.adaptor;
				const processor = new XSLTProcessor();
				const parsed = adaptor.parse( Mml3.XSLT, 'text/xml' );
				processor.importStylesheet( parsed );
				mml3.transform = ( node ) => {
					const div = adaptor.node( 'div', {}, [ adaptor.clone( node ) ] );
					const dom = adaptor.parse( adaptor.serializeXML( div ), 'text/xml' );
					const mml = processor.transformToDocument( dom );
					return ( mml ? adaptor.tags( mml, 'math' )[ 0 ] : node );
				};
				// inputJax[0] did not work as per https://github.com/mathjax/MathJax/issues/3287#issuecomment-2363843017
				const MML = window.MathJax.startup.document.inputJax[ 1 ];
				MML.mmlFilters.items.pop(); // remove old filter
				MML.mmlFilters.add( mml3.mmlFilter.bind( mml3 ) );
			}
		}
	};
}() );
