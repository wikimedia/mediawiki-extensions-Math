--
-- Used by the math module to keep track
-- of previously-rendered items.
--
CREATE TABLE /*_*/mathlatexml (
  -- Binary MD5 hash of math_inputtex, used as an identifier key.
  math_inputhash varbinary(16) NOT NULL PRIMARY KEY,
  -- the user input
  math_inputtex blob NOT NULL,
  -- the validated tex
  math_tex blob,
  -- MathML output LaTeXML
  math_mathml mediumblob,
  -- SVG output mathoid
  math_svg blob,
  -- MW_MATHSTYLE_(INLINE_DISPLAYSTYLE|DISPLAY|INLINE)
  math_style tinyint
) /*$wgDBTableOptions*/;
