<?php

namespace MediaWiki\Extension\Math\InputCheck;

use Exception;
use MathHooks;
use MathRenderer;
use MathRestbaseInterface;
use MathSource;
use stdClass;

/**
 * MediaWiki math extension
 *
 * @copyright 2002-2015 Tomasz Wegrzanowski, Brion Vibber, Moritz Schubotz,
 * and other MediaWiki contributors
 * @license GPL-2.0-or-later
 * @author Moritz Schubotz
 */
class RestbaseChecker extends BaseChecker {
	private $restbaseInterface;

	/**
	 * @param string $tex the TeX input string to be checked
	 * @param string $type
	 * @param MathRestbaseInterface|null &$ref
	 */
	public function __construct( $tex = '', $type = 'tex', &$ref = null ) {
		parent::__construct( $tex );
		if ( $ref ) {
			$this->restbaseInterface = $ref;
		} else {
			$this->restbaseInterface = new MathRestbaseInterface( $tex, $type );
			$ref = $this->restbaseInterface;
		}
	}

	/**
	 * @see https://phabricator.wikimedia.org/T119300
	 * @param stdClass $e
	 * @param MathRenderer|null $errorRenderer
	 * @return string|null
	 */
	public function errorObjectToHtml( stdClass $e, $errorRenderer = null ) {
		if ( $errorRenderer === null ) {
			$errorRenderer = new MathSource( $this->inputTeX );
		}
		if ( isset( $e->error->message ) ) {
			if ( $e->error->message === 'Illegal TeX function' ) {
				return $errorRenderer->getError( 'math_unknown_function', $e->error->found );
			} elseif ( preg_match( '/Math extension/', $e->error->message ) ) {
				$names = MathHooks::getMathNames();
				$mode = $names['mathml'];
				try {
					$host = $this->restbaseInterface->getUrl( '' );
				}
				catch ( Exception $ignore ) {
					$host = 'invalid';
				}
				$msg = $e->error->message;

				return $errorRenderer->getError( 'math_invalidresponse', $mode, $host, $msg );
			}

			return $errorRenderer->getError( 'math_syntax_error' );
		}

		return $errorRenderer->getError( 'math_unknown_error' );
	}

	/**
	 * @return bool
	 */
	public function isValid() {
		return $this->restbaseInterface->getSuccess();
	}

	/**
	 * Some TeX checking programs may return
	 * a modified tex string after having checked it.
	 * You can get the altered tex string with this method
	 * @return string A valid Tex string
	 */
	public function getValidTex() {
		return $this->restbaseInterface->getCheckedTex();
	}

	/**
	 * Returns the string of the last error.
	 * @return string|null
	 */
	public function getError() {
		$err = $this->restbaseInterface->getError();
		if ( $err === null ) {
			return null;
		}

		return $this->errorObjectToHtml( $err );
	}

	public function getRbi() {
		return $this->restbaseInterface;
	}

}
