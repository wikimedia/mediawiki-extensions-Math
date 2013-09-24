--
-- Used by the math module to keep track
-- of previously-rendered items.
--
CREATE TABLE /*_*/math (
  -- Binary MD5 hash of the latex fragment, used as an identifier key.
  math_inputhash varbinary(16) NOT NULL,

  -- MathML output from texvc, or from LaTeXML
  math_mathml text
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/math_inputhash ON /*_*/math (math_inputhash);
