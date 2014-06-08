--
-- Used by the math module to keep track
-- of previously-rendered items.
--
CREATE TABLE /*_*/mathlatexml (
  -- Binary MD5 hash of math_inputtex, used as an identifier key.
  math_inputhash BYTEA PRIMARY KEY,
  -- the user input
  math_inputtex TEXT NOT NULL,
  -- the validated tex
  math_tex TEXT,
  -- MathML output LaTeXML
  math_mathml TEXT,
  -- SVG output mathoid
  math_svg TEXT,
  -- MW_MATHSTYLE_(INLINE_DISPLAYSTYLE|DISPLAY|INLINE)
  math_style SMALLINT
) /*$wgDBTableOptions*/;
