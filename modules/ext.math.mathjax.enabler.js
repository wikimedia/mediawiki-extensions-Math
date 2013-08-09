/**
 * From https://en.wikipedia.org/wiki/User:Nageh/mathJax.js
 */
/*global mathJax:true, MathJax */
( function ( mw, $ ) {
  if ( typeof mathJax === 'undefined' ) {
    mathJax = {};
  }

  mathJax.version = '0.2';

  mathJax.loaded = false;
  mathJax.config = $.extend( true, {
    root: mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax/unpacked/',
    'v1.0-compatible': false,
    displayAlign: 'left',
    menuSettings: {
      locale: mw.config.get('wgUserLanguage'),
      zoom: 'Click'
    },
    'HTML-CSS': {
      imageFont: null,
      availableFonts: ['TeX'],
      mtextFontInherit: true
    },
    MathMenu: {
      showLocale: false
    },
    extensions: ['MathEvents.js','MathZoom.js','MathMenu.js','toMathML.js'],
    jax: ['input/TeX','output/HTML-CSS'],
    TeX: { extensions: ['noUndefined.js','AMSmath.js','AMSsymbols.js','texvc.js', 'color.js', 'cancel.js'] }
  }, mathJax.config );

  mathJax.Config = function () {
    MathJax.Hub.Config( mathJax.config );
    MathJax.Localization.resetLocale(mw.config.get('wgUserLanguage'));
    MathJax.OutputJax.fontDir = mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax/fonts';
    mw.loader.using( 'ext.math.mathjax.localization', function() {
      MathJax.Hub.Configured();
    });
  };

  /**
   * Renders all Math TeX inside the given elements.
   * @param {function} callback to be executed after text elements have rendered [optional]
   */
  $.fn.renderTex = function ( callback ) {
    var elem = this.find( '.tex' ).parent().toArray();

    if ( !$.isFunction( callback ) ) {
      callback = $.noop;
    }

    function render () {
      MathJax.Hub.Queue( ['Typeset', MathJax.Hub, elem, callback] );
    }

    mw.loader.using( 'ext.math.mathjax', function () {
      if ( MathJax.isReady ) {
        render();
      } else {
        MathJax.Hub.Startup.signal.MessageHook( 'End', render );
      }
    });
    return this;
  };

  mathJax.Load = function () {
    if (this.loaded) {
      return true;
    }

    mw.loader.using( 'ext.math.mathjax', this.Config);

    this.loaded = true;

    return false;
  };

  $( document ).ready( function () {
    mathJax.Load();
  } );

}( mediaWiki, jQuery ) );
