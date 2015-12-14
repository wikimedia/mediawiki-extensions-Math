<?php

use ValueValidators\Result;
use ValueValidators\ValueValidator;
use ValueValidators\Error;
use DataValues\StringValue;

// @author Duc Linh Tran, Julian Hilbig, Moritz Schubotz

class MathValidator implements ValueValidator {

	/**
	 * Parses a value.
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 */
	public function validate( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new IllegalValueException( '$value must be a StringValue' );
		}

		// get input String from value
		$tex = $value->getValue();

		$checker = new MathInputCheckRestbase( $tex );
		if ( $checker->isValid() )  {
			return Result::newSuccess();
		}

		// TeX string is not valid
		return Result::newError(
			array(
				Error::newError( null, null, 'malformed-value', $checker->getError() )
			)
		);
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}
}
