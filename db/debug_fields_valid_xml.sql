--
-- Used by the math module to keep extra information for debugging
--
ALTER TABLE /*_*/math ADD valid_xml tinyint(1);
ALTER TABLE /*_*/math ADD KEY `valid_xml` (`valid_xml`);