--
-- Used by the math module to keep track
-- of previously-rendered items.
--
CREATE TABLE /*$wgDBprefix*/mathoid (
   math_inputhash varbinary(16) NOT NULL PRIMARY KEY,
   math_inputtex NVARCHAR(MAX),
   math_tex NVARCHAR(MAX),
   math_mathml NVARCHAR(MAX),
   math_svg NVARCHAR(MAX)
);
