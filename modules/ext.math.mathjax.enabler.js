/**
 * From https://en.wikipedia.org/wiki/User:Nageh/mathJax.js
 */
/*global mathJax:true, MathJax:true */
( function ( mw, $ ) {
  if ( typeof mathJax === 'undefined' ) {
    mathJax = {};
  }

  mathJax.version = '0.2';

  mathJax.loaded = false;

  mathJax.locale = mw.config.get('wgUserLanguage');
  mathJax.config = $.extend( true, {
    root: mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax/unpacked',
    'v1.0-compatible': false,
    displayAlign: 'left',
    menuSettings: {
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
    jax: ['input/TeX','input/MathML','output/HTML-CSS']
  }, mathJax.config );

  mathJax.Init = function () {
    // Configure MathJax
    MathJax.Hub.Config( mathJax.config );
    MathJax.OutputJax.fontDir = mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax/fonts';

    // Set MathJax's locale
    MathJax.Localization.resetLocale(mathJax.locale);
    mathJax.locale = MathJax.Localization.locale;
    MathJax.Hub.config.menuSettings.locale = mathJax.locale;

    // Redefine MathJax.Hub.Startup.Jax
    MathJax.Hub.Startup.Jax = function () {
      var config, jax, i, k, name, queue, callback;
      //  Save the order of the output jax since they are loading asynchronously
      config = MathJax.Hub.config;
      jax = MathJax.Hub.outputJax;
      for ( i = 0, k = 0; i < config.jax.length; i++ ) {
        name = config.jax[i].substr(7);
        if (config.jax[i].substr(0,7) === 'output/' && jax.order[name] === null) {
            jax.order[name] = k;
            k++;
        }
      }
      queue = MathJax.Callback.Queue();
      callback = MathJax.Callback({});
      return queue.Push(
        ['Post', MathJax.Hub.Startup.signal, 'Begin Jax'],
        ['using', mw.loader, 'ext.math.mathjax.jax.config', callback],
        callback,
        ['Post', MathJax.Hub.Startup.signal, 'End Jax']
      );
    };

    // Redefine MathJax.Hub.Startup.Extensions
    MathJax.Hub.Startup.Extensions = function () {
      var queue, callback;
      queue = MathJax.Callback.Queue();
      callback = MathJax.Callback({});
      return queue.Push(
        ['Post', MathJax.Hub.Startup.signal, 'Begin Extensions'],
        ['using', mw.loader, 'ext.math.mathjax.all', callback],
        callback,
        ['Post', MathJax.Hub.Startup.signal, 'End Extensions']
      );
    };

    // Now continue the MathJax's startup sequence
    MathJax.Hub.Configured();
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

    // create the global MathJax variable to hook into MathJax startup
    MathJax = {
      delayStartupUntil: 'configured',
      AuthorInit: mathJax.Init
    };

    // create startup element
    mw.loader.load('ext.math.mathjax');

    this.loaded = true;

    return false;
  };

  $( document ).ready( function () {
    mathJax.Load();
  } );

}( mediaWiki, jQuery ) );
