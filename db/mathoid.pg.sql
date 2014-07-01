CREATE TABLE math (
  math_inputhash              BYTEA     NOT NULL  UNIQUE,
  math_inputtex               TEXT,
  math_tex                    TEXT,
  math_mathml                 TEXT,
  math_svg                    TEXT
);
