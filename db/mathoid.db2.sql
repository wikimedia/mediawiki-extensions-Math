CREATE TABLE math (
  math_inputhash              VARCHAR(16) FOR BIT DATA     NOT NULL  UNIQUE,
  math_inputtex               CLOB(64K) INLINE LENGTH 4096,
  math_tex                    CLOB(64K) INLINE LENGTH 4096,
  math_mathml                 CLOB(64K) INLINE LENGTH 4096,
  math_svg                    CLOB(64K) INLINE LENGTH 4096,
);
