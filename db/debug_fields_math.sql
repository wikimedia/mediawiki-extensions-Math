--
-- Used by the math module to keep extra information for debugging
--
ALTER TABLE /*_*/math ADD math_tex text;
ALTER TABLE /*_*/math Add math_status int(4);
ALTER TABLE /*_*/math ADD valid_xml tinyint(1);
ALTER TABLE /*_*/math ADD math_log text;
ALTER TABLE /*_*/math ADD math_timestamp timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE /*_*/math ADD KEY `math_timestamp` (`math_timestamp`);
ALTER TABLE /*_*/math ADD KEY `math_status` (`math_status`);
ALTER TABLE /*_*/math ADD KEY `valid_xml` (`valid_xml`);