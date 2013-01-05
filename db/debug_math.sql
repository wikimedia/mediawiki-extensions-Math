--
-- Used by the math module to keep track
-- of previously-rendered items.
--
CREATE TABLE /*_*/math (
  -- Binary MD5 hash of the latex fragment, used as an identifier key.
  math_inputhash varbinary(16) NOT NULL,

  -- Not sure what this is, exactly...
  math_outputhash varbinary(16) NOT NULL,

  -- texvc reports how well it thinks the HTML conversion worked;
  -- if it's a low level the PNG rendering may be preferred.
  math_html_conservativeness tinyint NOT NULL,

  -- HTML output from texvc, if any
  math_html text,

  -- MathML output from texvc, if any
  math_mathml text,
  
  math_tex text,
  
  math_status int(4),
  
  valid_xml tinyint(1),
  
  math_log text,
  
  math_timestamp timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  KEY `math_timestamp` (`math_timestamp`),
  KEY `math_status` (`math_status`),
  KEY `valid_xml` (`valid_xml`)  
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX /*i*/math_inputhash ON /*_*/math (math_inputhash);