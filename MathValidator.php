<?php

use ValueValidators\Result;
use ValueValidators\ValueValidator;
use ValueValidators\Error;

// @author Duc Linh Tran, Julian Hilbig, Moritz Schubotz

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
		// get input String from value
		$tex = $value->getValue();

		$renderer =  new MathMathML( $tex );

		// perform checkTex on our input string to see if it's a valid TeX string
		if ( $renderer->checkTex() )  {
			return Result::newSuccess();
		}

		// checkTex failed
		return Result::newError(
			array(
				Error::newError( null, null, 'malformed-value', 'not a valid TeX string.' )
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
