<?php

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

/*
 * Format the tex-String to HTML, used in Wikidata, displaying the values in items
 */
class MathFormatterHtml implements ValueFormatter {

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
		// TODO: Research what kind of formatting fits best
		$tex = $value->getValue();
		return "$tex"; // Place holder 
	}
}

