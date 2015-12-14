<?php

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;

class MathFormatter implements ValueFormatter{

	/**
	 * Formats a value.
	 *
	 * @since 0.1
	 *
	 * @param mixed $value The value to format
	 *
	 * @return mixed
	 * @throws \ValueFormatters\FormattingException
	 */
	public function format(  $value ) {
		$type=$value->getValue();
		return "<math>$type</math>";
		// TODO: Implement format() method.
	}
}