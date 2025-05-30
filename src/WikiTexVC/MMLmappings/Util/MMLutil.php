<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util;

use IntlChar;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;

/**
 * Utility Methods for parsing Tex to MathML
 * @author Johannes Stegmüller
 */
class MMLutil {

	/**
	 * Recognize if input is a Unicode string "\\u1235"
	 * If yes converts it to notation "&#x123;", if no just returns the input.
	 * @param string $input input to be checked
	 * @return string modified input or input
	 */
	public static function uc2xNotation( string $input ): string {
		if ( str_starts_with( $input, "\\u" ) ) {
			return str_replace( "\\u", "&#x", $input ) . ";";
		}
		return $input;
	}

	public static function number2xNotation( string $input ): string {
		return "&#x" . $input . ";";
	}

	/**
	 * Rounds a floating point input to three digits precision
	 * and returns it as string with succeeding "em".
	 * @param float $size input to be processed
	 * @return string rounded digits with em
	 */
	public static function round2em( float $size ) {
		$rounded = round( $size, 3 );
		return $rounded . "em";
	}

	/**
	 * In a floating point digit as string, set input to precision of three digits
	 * without rounding.
	 * @param string $size input to be checked
	 * @return string digits of precision three with em
	 */
	public static function size2em( string $size ): string {
		return preg_replace( "/(\.\d\d\d).+/", '$1', $size ) . "em";
	}

	/**
	 * Assumes the input curly contains an TexArray of literals, squashes the TexArray characters to a string.
	 * @param TexArray $node TexArray of literals
	 * @return ?string squashed string in example "2mu", "-3mu" etc. Null if no TexArray inside curly.
	 */
	public static function squashLitsToUnit( TexArray $node ): ?string {
		$unit = "";
		foreach ( $node as $literal ) {
			if ( !$literal instanceof Literal ) {
				continue;
			}
			$unit .= $literal->getArg();
		}

		return $unit;
	}

	/**
	 * em or other dimensional unit gets multiplied by pre-operator.
	 * @param string $size input size i.e-123em
	 * @param string $operator plus (+) or minus (-)
	 * @return string ++ => + , -- => +, -+ => -
	 */
	public static function addPreOperator( string $size, string $operator ): string {
		$emtr = trim( $size );

		$ok = preg_match( "/^([+\-])$/", $operator );
		if ( !$ok ) {
			return '';
		}
		switch ( $emtr[0] ) {
			case "-":
				if ( $operator == "+" ) {
					return $emtr;
				} elseif ( $operator == "-" ) {
					$emtr[0] = "+";
					return $emtr;
				}
				break;
			case "+":
				if ( $operator == "+" ) {
					return $emtr;
				} elseif ( $operator == "-" ) {
					$emtr[0] = "-";
					return $emtr;
				}
				break;
			default:
				return $operator . $emtr;
		}
		return $emtr;
	}

	/**
	 * Convert a length dimension to em format
	 * currently supports "mu: math unit and forwards em"
	 * @param string $dimen input for length dimension  like "-2mu" or "3 em"
	 * @return string|null converted string i.e. "0.333em"  or null if error
	 */
	public static function dimen2em( string $dimen ): ?string {
		$matches = [];
		$matched = preg_match( '/([+-]?)(\d*\.*\d+)\s*(mu|em)/', $dimen, $matches );

		if ( !$matched ) {
			return null;
		}
		if ( $matches[3] == "mu" ) {
			$ret = self::size2em( strval( intval( $matches[2] ) / 18 ) );
		} elseif ( $matches[3] == "em" ) {
			$ret = $matches[2] . "em";
		} else {
			return null;
		}

		return ( $matches[1] == "-" ? "-" : "" ) . $ret;
	}

	public static function createEntity( string $code ): ?string {
		return IntlChar::chr( intval( $code, 16 ) );
	}

	/**
	 * Assumes the input curly contains an TexArray of literals, squashes the TexArray characters to a string.
	 * It checks for dollar escaping and removes a backslash, also renders DQ args with underscores
	 * @param TexNode $node curly containing a TexArray of literals
	 * @return ?string squashed string in example "2mu", "-3mu" etc. Null if no TexArray inside curly.
	 */
	public static function squashLitsToUnitIntent( TexNode $node ): ?string {
		if ( !$node->isCurly() ) {
			return null;
		}
		$unit = "";
		foreach ( $node->getArgs() as $literal ) {
			if ( $literal instanceof DQ ) {
				$args = $literal->getArgs();
				if ( !$args[0] instanceof Literal || !$args[1] instanceof Literal ) {
					continue;
				}
				$arg = self::removeDollarEscaping( $args[0]->getArgs()[0] ) . "_"
					. self::removeDollarEscaping( $args[1]->getArgs()[0] );
			} else {
				if ( !$literal instanceof Literal ) {
					continue;
				}
				$arg = self::removeDollarEscaping( $literal->getArg() );
			}
			$unit .= $arg;
		}
		return $unit;
	}

	public static function removeDollarEscaping( string $input ): string {
		if ( $input == "\\$" ) {
			return "\$";
		}
		return $input;
	}

}
