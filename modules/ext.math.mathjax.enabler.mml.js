( function ( mw ) {
  var isGecko = navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') === -1;
  if (isGecko) {
    // Gecko browsers have good MathML support but require math fonts, so we
    // make them available as Web fonts if they are not installed on the system.
    // See https://developer.mozilla.org/docs/Mozilla/MathML_Project/Fonts
    mw.loader.load('ext.math.fonts');
  } else {
    // Other browsers require MathJax to render MathML properly.
    mw.loader.load('ext.math.mathjax.enabler');
  }
}( mediaWiki ) );