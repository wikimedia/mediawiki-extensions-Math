<?php

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/*
 * Format the tex-String to plain Text, used in adding values to properties in items
 */
class MathFormatterPlain implements ValueFormatter {

	/*
	 * Formats the tex String.
	 * @since 0.1
	 *
	 * @param String
	 *
	 * @return String
	 * @throws \ValueFormatters\FormattingException
	 */
	public function format( $value ) {
		$tex = $value->getValue();
		return "$tex";
	}
}

