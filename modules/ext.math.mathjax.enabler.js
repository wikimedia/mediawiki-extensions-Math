/**
 * From https://en.wikipedia.org/wiki/User:Nageh/mathJax.js
 */
/*global mathJax:true, MathJax, wikEd:true */
( function ( mw, $ ) {
  if ( typeof mathJax === 'undefined' ) {
    mathJax = {};
  }

  mathJax.version = '0.2';

  mathJax.loaded = false;

  mathJax.config = $.extend( true, {
    root: mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax',
    config: ['TeX-AMS-texvc_HTML.js'],
    'v1.0-compatible': false,
    styles: {
      '.mtext': {
        'font-family': 'sans-serif ! important',
        'font-size': '80%'
      }
    },
    displayAlign: 'left',
    menuSettings: {
      zoom: 'Click'
    },
    'HTML-CSS': {
      imageFont: null,
      availableFonts: ['TeX']
    }
  }, mathJax.config );

  mathJax.Config = function () {
    MathJax.Hub.Config( mathJax.config );
    MathJax.OutputJax.fontDir = mw.config.get('wgExtensionAssetsPath') + '/Math/modules/MathJax/fonts';
  };

  mathJax.Load = function () {
    var config, script;
    if (this.loaded) {
      return true;
    }

    // create configuration element
    config = 'mathJax.Config();';
    script = document.createElement( 'script' );
    script.setAttribute( 'type', 'text/x-mathjax-config' );
    if ( window.opera ) {
      script.innerHTML = config;
    } else {
      script.text = config;
    }
    document.getElementsByTagName('head')[0].appendChild( script );

    // create startup element
    mw.loader.load('ext.math.mathjax');

    this.loaded = true;

    return false;
  };

  mathJax.Init = function () {
    this.Load( document.getElementById('bodyContent') || document.body );

    // compatibility with wikEd
    if ( typeof wikEd === 'undefined' ) {
      wikEd = {};
    }
    if ( wikEd.config === undefined ) {
      wikEd.config = {};
    }
    if ( wikEd.config.previewHook === undefined ) {
      wikEd.config.previewHook = [];
    }
    wikEd.config.previewHook.push( function (){
      if (window.mathJax.Load(document.getElementById('wikEdPreviewBox') || document.body)) {
        MathJax.Hub.Queue(['Typeset', MathJax.Hub, 'wikEdPreviewBox']);
      }
    } );

    // compatibility with ajaxPreview
    this.oldAjaxPreviewExec = window.ajaxPreviewExec;
    window.ajaxPreviewExec = function (previewArea) {
      if ( mathJax.oldAjaxPreviewExec !== undefined ) {
        mathJax.oldAjaxPreviewExec(previewArea);
      }
      if ( mathJax.Load(previewArea) ) {
        MathJax.Hub.Queue( ['Typeset', MathJax.Hub, previewArea] );
      }
    };
  };

  $( document ).ready( function () {
    mathJax.Init();
  } );

}( mediaWiki, jQuery ) );
