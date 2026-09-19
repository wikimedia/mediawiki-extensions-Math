'use strict';

const REFERENCE_URL = mw.config.get( 'wgExtensionAssetsPath' ) +
	'/Math/tests/phpunit/integration/WikiTexVC/data/reference.json';

// MJX-<n>- ids are order-dependent per page; strip the counter for a stable
// snapshot. A near-zero vertical-align can also round to "0px" on one platform
// and land exactly on "baseline" on another; both mean the same thing, so
// treat them as equal too.
function normalizeSvg( svg ) {
	return svg
		.replace( /MJX-\d+-/g, 'MJX-' )
		.replace( /vertical-align:\s*-?0(?:px|ex|em);/g, 'vertical-align: baseline;' );
}

// A character the math font has no vector glyph for falls back to a plain
// <text> element sized by whatever system font the OS happens to have; that
// size genuinely differs between platforms, so such entries are stored but
// not strictly compared.
function hasFontFallback( svg ) {
	return /<text\b/.test( svg );
}

// \\[30px]-style row spacing (T438415) is a literal CSS px value that MathJax
// converts to its internal ex units using the current font's live metrics;
// that conversion, and so the whole row layout, genuinely varies by platform.
function hasPixelRowSpacing( mathml ) {
	return /padding-bottom:\s*[\d.]+px/.test( mathml );
}

// Null when nothing about this entry is known to render differently by
// platform; otherwise a short label for the warning message.
function platformDependentReason( svg, mathml ) {
	if ( hasFontFallback( svg ) ) {
		return 'font-fallback glyph';
	}
	if ( hasPixelRowSpacing( mathml ) ) {
		return 'px-based row spacing';
	}
	return null;
}

function flattenEntries( json ) {
	const cases = [];
	for ( const [ hash, entry ] of Object.entries( json ) ) {
		if ( entry.outputs ) {
			entry.outputs.forEach( ( reference, index ) => {
				cases.push( { hash, index, reference } );
			} );
		} else {
			cases.push( { hash, index: null, reference: entry } );
		}
	}
	return cases;
}

// To trigger ui/lazy, we'd need to append to document.body instead of #qunit-fixture,
// because the fixture is off-screen.
//
// Optimization: Bypass overhead of ui/lazy (avoid delay of browser eventloop, avoid
// would-be delay of polling for `<svg>`) and instead leave the element detached from
// the document, and render via lazyAlwaysTypeset in fast synchronous fashion.
// This is the difference between waiting 12s and waiting <1s.
const nonLazyContainer = document.createElement( 'div' );

async function renderSvg( mathml ) {
	const div = document.createElement( 'div' );
	div.innerHTML = mathml;
	nonLazyContainer.appendChild( div );
	await window.MathJax.typesetPromise( [ div ] );
	const svg = div.querySelector( 'svg' );
	const html = svg ? normalizeSvg( svg.outerHTML ) : null;
	div.remove();
	return html;
}

// Optimization: Start fetch in the background now, await later during test if not yet finished.
const pReferenceData = fetch( REFERENCE_URL ).then( ( resp ) => resp.json() );

QUnit.module( 'ext.math.mathjax.svgReference', () => {

	QUnit.test( 'client-side SVG matches stored reference.json snapshots', async ( assert ) => {
		await window.MathJax.startup.promise;
		// Config is copied at startup; only the live document's options take effect.
		window.MathJax.startup.document.options.lazyAlwaysTypeset = [ nonLazyContainer ];
		const referenceData = await pReferenceData;
		const cases = flattenEntries( referenceData );

		for ( const { hash, index, reference } of cases ) {
			if ( reference.skipped || reference.error || !reference.output ) {
				continue;
			}
			const label = index === null ? hash : hash + ' [' + index + ']';
			const svg = await renderSvg( reference.output );
			assert.true( svg !== null, label + ' renders to SVG' );
			if ( svg === null ) {
				continue;
			}
			const changed = svg !== reference.svg;
			const platformReason = platformDependentReason( svg, reference.output );
			if ( !platformReason && reference.svg !== undefined ) {
				assert.strictEqual( svg, reference.svg, label + ' SVG unchanged' );
			} else if ( platformReason && changed ) {
				// Stored for reference, but not asserted: it varies by platform,
				// so a mismatch alone isn't a regression.
				// eslint-disable-next-line no-console
				console.warn( label + ': ' + platformReason + ' changed, not asserted' );
			}
			if ( changed ) {
				// Browser JS can't write files, so FixNativeReferences.php scrapes
				// this line to (re)populate reference.json instead.
				// eslint-disable-next-line no-console
				console.log( 'SVG_REFERENCE_DATA:' + JSON.stringify( { hash, index, svg } ) );
			}
		}
	} );

} );
