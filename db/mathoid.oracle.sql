CREATE TABLE &mw_prefix.math (
  math_inputhash              VARCHAR2(32)      NOT NULL,
  math_inputtex               CLOB,
  math_inputtype              SMALLINT,
  math_tex                    CLOB,
  math_mathml                 CLOB,
  math_svg                    CLOB,
  math_style                  SMALLINT
);
CREATE UNIQUE INDEX &mw_prefix.math_u01 ON &mw_prefix.math (math_inputhash);
