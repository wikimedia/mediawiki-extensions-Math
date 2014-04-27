CREATE TABLE &mw_prefix.math (
  math_inputhash              VARCHAR2(32)      NOT NULL,
  math_outputhash             VARCHAR2(32)      NOT NULL,
  math_html_conservativeness  NUMBER  NOT NULL,
  math_html                   CLOB,
  math_mathml                 CLOB,
  math_img_width              NUMBER,
  math_img_height             NUMBER
);
CREATE UNIQUE INDEX &mw_prefix.math_u01 ON &mw_prefix.math (math_inputhash);
