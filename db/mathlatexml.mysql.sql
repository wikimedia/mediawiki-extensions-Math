-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: Math/sql/mathlatexml.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/mathlatexml (
  math_inputhash VARBINARY(16) NOT NULL,
  math_inputtex TEXT NOT NULL,
  math_tex TEXT DEFAULT NULL,
  math_mathml MEDIUMTEXT DEFAULT NULL,
  math_svg TEXT DEFAULT NULL,
  math_style TINYINT DEFAULT NULL,
  PRIMARY KEY(math_inputhash)
) /*$wgDBTableOptions*/;
