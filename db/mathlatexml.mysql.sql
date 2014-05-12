--
-- Used by the math module to keep track
-- of previously-rendered items.
--
CREATE TABLE /*_*/mathlatexml (
  -- Binary MD5 hash of math_inputtex, used as an identifier key.
  math_inputhash varbinary(16) NOT NULL PRIMARY KEY,
  -- the user input
  math_inputtex text NOT NULL,
  -- the validated tex
  math_tex text,
  -- MathML output LaTeXML
  math_mathml text,
  -- SVG output mathoid
  math_svg text,
  -- return status of LaTeXML
  math_status int(4),
  -- flag if MathML is valid XML
  valid_xml tinyint(1),
  -- LOG output of LaTeXML
  math_log text,
  -- Timestamp of the last update
  math_timestamp timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Indexes to find broken math
  KEY math_timestamp (math_timestamp),
  KEY math_status (math_status),
  KEY valid_xml (valid_xml)
) /*$wgDBTableOptions*/;
