<?php

use MediaWiki\Extension\Math\InputCheck\InputCheckFactory;

return [
	'MathCheckerFactory' => function () : InputCheckFactory {
		return new InputCheckFactory();
	} ];
