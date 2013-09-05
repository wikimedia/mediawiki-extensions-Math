( function ( mw ) {
  var isGecko = navigator.userAgent.indexOf('Gecko') > -1 && navigator.userAgent.indexOf('KHTML') === -1;
  if (!isGecko) {
    mw.loader.load('ext.math.mathjax.enabler');
  }
}( mediaWiki ) );