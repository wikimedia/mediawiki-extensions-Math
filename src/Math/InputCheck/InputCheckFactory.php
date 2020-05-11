<?php

namespace MediaWiki\Extension\Math\InputCheck;

class InputCheckFactory {

	public function getChecker( $input, $type ) {
		return new MathoidChecker( $input, $type );
	}
}
