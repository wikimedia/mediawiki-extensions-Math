/**
 * Try to choose the best math rendering option.
 */
( function ( mw ) {
  var ua = navigator.userAgent,
      isGecko = ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1,
      isMSIE = (window.ActiveXObject !== null && window.clipboardData !== null),
      hasMathPlayer = ua.indexOf('MathPlayer');

  if (isGecko) {
    // Gecko browsers have good MathML support but require math fonts, so we
    // make them available as Web fonts if they are not installed on the system.
    // (https://developer.mozilla.org/docs/Mozilla/MathML_Project/Fonts)
    // TODO: do the same for future versions of WebKit when they have better
    // MathML support.
    mw.loader.load('ext.math.fonts');
    mw.loader.load('ext.math.mathml');
  } else if (isMSIE) {
    if (hasMathPlayer && !window.atob) {
      // Internet Explorer is able to render MathML when MathPlayer is installed
      // but this plugin has compatibility issues with versions 10 and 11.
      // (http://news.dessci.com/2013/10/microsoft-cripples-display-math-ie10-11.html)
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
