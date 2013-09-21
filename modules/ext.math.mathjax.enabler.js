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

  // See http://docs.mathjax.org/en/latest/options/index.html
  mathJax.config = $.extend( true, {
    root: mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax/unpacked',
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
    }
    },
    MathMenu: {
      showLocale: false
    },
    jax: ['input/TeX','input/MathML','output/NativeMML','output/HTML-CSS']
  }, mathJax.config );

  /**
   * @param {string} relative path to a MathJax file
   * @return {string} MediaWiki module containing the file
   */
  mathJax.getModuleNameFromFile = function (file) {
    var regexp, module;

    // These modules are loaded at startup and thus don't need to be specified:
    // - ext.math.mathjax.mathjax in mathJax.Load
    // - ext.math.mathjax.localization in mathJax.Init
    // - ext.math.mathjax.jax.config in MathJax.Hub.Startup.Jax
    // - ext.math.mathjax.extensions in MathJax.Hub.Startup.Extensions
    //

    module = 'ext.math.mathjax.';

    regexp = file.match(/(.*)\/jax\.js/);
    if ( regexp ) {
      // These are jax.js files of input, element or output modules:
      // - ext.math.mathjax.jax.input.MathML
      // - ext.math.mathjax.jax.input.TeX
      // - ext.math.mathjax.jax.output.HTML-CSS
      // - ext.math.mathjax.jax.output.NativeMML
      // - ext.math.mathjax.jax.output.HTML-CSS
      // - ext.math.mathjax.jax.output.SVG
      return module + regexp[1].replace(/\//g,'.');
    }

    if ( file.match(/jax\/element\/mml\/optable/) ) {
      return module + 'jax.element.mml.optable';
    }

    regexp = file.match(/jax\/output\/(HTML-CSS|SVG)/);
    if ( regexp ) {
      module += 'jax.output.' + regexp[1] + '.';
      if ( file.match(/autoload/) ) {
        return module + 'autoload';
      }
      if ( file.match(/fonts\/TeX\/fontdata/) ) {
        return module + 'fonts.TeX.fontdata';
      }
      if ( file.match(/fonts\/TeX\/.*\/.*\/Main\.js/) ) {
        return module + 'fonts.TeX.MainJS';
      }
      if ( file.match(/fonts\/TeX\/Main/) ) {
        return module + 'fonts.TeX.Main';
      }
      if ( file.match(/fonts\/TeX\/AMS/) ) {
        return module + 'fonts.TeX.AMS';
      }
      if ( file.match(/fonts\/TeX/) ) {
        return module + 'fonts.TeX.Extra';
      }
    }

    // FIXME: report that in debug mode?
    // console.warn( 'MathJax resource not handled by MediaWiki: ' + file );

    return null;
  };

  /**
   * Configure MathJax, preload some files and replace MathJax's resource loader
   * by our own resource loader.
   */
  mathJax.Init = function () {
    // Configure MathJax
    MathJax.Hub.Config( mathJax.config );
    MathJax.OutputJax.fontDir = mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax/fonts';

    // Redefine MathJax.Hub.Startup.Jax
    MathJax.Hub.Startup.Jax = function () {
      var config, jax, i, k, name, queue, callback;
      //  Save the order of the output jax since they are loading asynchronously
      config = MathJax.Hub.config;
      jax = MathJax.Hub.outputJax;
      for ( i = 0, k = 0; i < config.jax.length; i++ ) {
        name = config.jax[i].substr(7);
        if ( config.jax[i].substr(0,7) === 'output/' && jax.order[name] === null ) {
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
        ['using', mw.loader, 'ext.math.mathjax.extensions', callback],
        callback,
        ['Post', MathJax.Hub.Startup.signal, 'End Extensions']
      );
    };

    // Redefine MathJax.Ajax.Load
    MathJax.Ajax.MathJaxLoad = MathJax.Ajax.Load;
    MathJax.Ajax.Load = function (file, callback) {
      var type, i, module;
      callback = MathJax.Callback(callback);
      if ( file instanceof Object ) {
        for ( i in file ) {
          if ( file.hasOwnProperty(i) ) {
            type = i.toUpperCase();
            file = file[i];
          }
        }
      } else {
        type = file.split(/\./).pop().toUpperCase();
      }
      file = MathJax.Ajax.fileURL(file);
      if ( MathJax.Ajax.loading[file] ) {
        MathJax.Ajax.addHook(file, callback);
      } else {
        if ( MathJax.Ajax.loader[type] ) {
          module = mathJax.getModuleNameFromFile(file.substring(MathJax.Hub.config.root.length + 1));
          if ( module ) {
            // Use MediaWiki's resource loader.
            MathJax.Ajax.loading[file] = {
              callback: callback,
              timeout: -1,
              status: this.STATUS.OK,
              script: null
            };
            // Add this to the structure above after it is created to prevent
            // recursion when loading the initial localization file (before
            // loading message is available)
            MathJax.Ajax.loading[file].message = MathJax.Message.File(file);
            mw.loader.load(module);
          } else {
            // Fallback to MathJax's own loader.
            callback = MathJax.Ajax.MathJaxLoad(file, callback);
          }
        } else {
          throw new Error('Can\'t load files of type ' + type);
        }
      }
      return callback;
    };

    // Set MathJax's locale and load the localization data.
    MathJax.Localization.resetLocale(mathJax.locale);
    mathJax.locale = MathJax.Localization.locale;
    MathJax.Hub.config.menuSettings.locale = mathJax.locale;
    mw.loader.using( 'ext.math.mathjax.localization', function () {
      MathJax.Hub.Configured();
    } );
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

    mw.loader.using( 'ext.math.mathjax.mathjax', function () {
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

    // load MathJax.js
    mw.loader.load('ext.math.mathjax.mathjax');

    this.loaded = true;

    return false;
  };

  $( document ).ready( function () {
    mathJax.Load();
  } );

}( mediaWiki, jQuery ) );
