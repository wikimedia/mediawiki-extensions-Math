<?php

use ValueValidators\Result;
use ValueValidators\ValueValidator;

class MathValidator implements ValueValidator {

	/**
	 * Parses a value.
	 *
	 * @since 0.1
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 */
	public function validate( $value ) {
		return Result::newSuccess();
	}

	/**
	 * Takes an associative array with options and sets those known to the ValueValidator.
	 *
	 * @since 0.1
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// TODO: Implement setOptions() method.
	}
}
