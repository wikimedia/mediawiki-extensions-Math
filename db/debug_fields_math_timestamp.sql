--
-- Used by the math module to keep extra information for debugging
--
ALTER TABLE /*_*/mathoid ADD math_timestamp timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE /*_*/mathoid ADD KEY `math_timestamp` (`math_timestamp`);
