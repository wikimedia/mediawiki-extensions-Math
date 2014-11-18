--
-- Used by the math module to oganize the log files from
-- different rendering engines
--
CREATE TABLE /*_*/mathlog (
  -- Binary MD5 hash of math_inputtex, used as an identifier key.
  math_inputhash varbinary(16) NOT NULL,
  -- the log input
  math_log text NOT NULL,
  -- the post request sent
  math_post text,
  -- MW_MATH_(MAHML|LATEXML)
  math_mode tinyint,
  -- time needed to answer the request in ms
  math_rederingtime int,
  -- statuscode returned by the rendering engine
  math_statuscode tinyint,
  -- timestamp
  math_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- key
  key ( math_inputhash, math_mode )
) /*$wgDBTableOptions*/;