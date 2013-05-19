MathJax.Hub.Config({
  extensions: MathJax.Hub.config.extensions.concat("wiki2jax.js","MathEvents.js","MathZoom.js","MathMenu.js","toMathML.js"),
  jax: ["input/TeX","output/HTML-CSS"],
  TeX: {extensions: ["noUndefined.js","AMSmath.js","AMSsymbols.js","texvc.js", "color.js", "cancel.js"]}
});
MathJax.Ajax.loadComplete("[MathJax]/config/TeX-AMS-texvc_HTML.js");
