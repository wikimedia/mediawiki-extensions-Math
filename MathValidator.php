<?php

use DataValues\StringValue;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

// @author Duc Linh Tran, Julian Hilbig, Moritz Schubotz

class MathValidator implements ValueValidator {

	/**
	 * Validates a value with MathInputCheckRestbase
	 *
	 * @param mixed $value The value to validate
	 *
	 * @return \ValueValidators\Result
	 * @throws ValueFormatters\Exceptions\MismatchingDataValueTypeException
	 */
	public function validate( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new MismatchingDataValueTypeException( 'StringValue', get_class( $value ) );
		}

		// get input String from value
		$tex = $value->getValue();

		$checker = new MathInputCheckRestbase( $tex );
		if ( $checker->isValid() )  {
			return Result::newSuccess();
		}

		// TeX string is not valid
		return Result::newError(
			[
				Error::newError( null, null, 'malformed-value', [ $checker->getError() ] )
			]
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
