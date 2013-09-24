--
-- Used by the math module to keep extra information for debugging
--
ALTER TABLE /*_*/math Add math_status int(4);
ALTER TABLE /*_*/math ADD KEY `math_status` (`math_status`);