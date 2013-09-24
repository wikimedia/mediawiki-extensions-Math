/**
 * Try to choose the best math rendering option.
 */
( function ( mw ) {
  var ua = navigator.userAgent,
      isGecko = ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1 &&
                ua.indexOf('Trident') === -1,
      isMSIE = (window.ActiveXObject !== null && window.clipboardData !== null),
      hasMathPlayer = ua.indexOf('MathPlayer') > -1;

  if (isGecko) {
    // Gecko browsers have good MathML support but require math fonts, so we
    // make them available as Web fonts if they are not installed on the system.
    // (https://developer.mozilla.org/docs/Mozilla/MathML_Project/Fonts)
    // TODO: do the same for future versions of WebKit when they have better
    // MathML support.
    mw.loader.load('ext.math.fonts');
    mw.loader.load('ext.math.mathml');
  } else if (isMSIE) {
    if (hasMathPlayer) {
      // Internet Explorer is able to render MathML when MathPlayer is installed
      mw.loader.load('ext.math.mathml');
    } else if (document.addEventListener) {
      // Use the SVG fallback for version >= 9.
      mw.loader.load('ext.math.svg');
    }
  } else {
    // At the moment, the MathML support in other rendering engines is not
    // good enough to render all the advanced notations allowed by MediaWiki.
    // Hence we fallback to SVG as well.
    mw.loader.load('ext.math.svg');
  }
}( mediaWiki ) );
