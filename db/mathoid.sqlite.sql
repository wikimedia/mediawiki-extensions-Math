--
-- Used by the math module to keep track
-- of previously-rendered items.
--
CREATE TABLE /*_*/mathoid (
  -- Binary MD5 hash of math_inputtex, used as an identifier key.
  math_inputhash varbinary(16) NOT NULL PRIMARY KEY,
  -- the user input
  math_input text NOT NULL,
  -- the validated tex
  math_tex text,
  -- MathML output LaTeXML
  math_mathml text,
  -- SVG output mathoid
  math_svg text,
  -- MW_MATHSTYLE_(INLINE_DISPLAYSTYLE|DISPLAY|INLINE)
  math_style tinyint,
  -- type of the Math input (TeX, MathML, AsciiMath...)
  math_input_type tinyint,
    -- png output of mathoid
  math_png blob
) /*$wgDBTableOptions*/;
