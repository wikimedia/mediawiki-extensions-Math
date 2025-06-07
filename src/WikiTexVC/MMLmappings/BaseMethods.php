<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use ArgumentCountError;
use LogicException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmerror;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

/**
 * This contains the basic parsing methods for tex elements, which get invoked
 * to check if there is a specific parsing function defined in the mappings
 * and then forward to the parsing function.
 *
 * Much of this is WIP since there are many cases.
 * @author Johannes Stegmüller
 */
class BaseMethods {

	public static function checkAndParse( $input, $passedArgs, $operatorContent, TexNode $node ) {
		if ( !is_string( $input ) ) {
			// just discard these elements, sometimes empty TexArray
			return null;
		}

		// Checking for a named parsing function

		if ( $input === '\\ ' ) {
			$resFct = [ 'macro', '\\text{ }' ];
		} else {
			$resFct = TexUtil::getInstance()->callback( trim( $input ) );
		}
		if ( $resFct == null ) {
			return null;
		}
		// If the function has been found, dynamically call the associated parsing function.
		if ( is_string( $resFct ) ) {
			$resFct = [ $resFct ];
		}
		if ( str_contains( $resFct[0], '::', ) ) {
			throw new LogicException( "Callback to $resFct[0] should be treated in the respective class." );
		}
		try {
			// Passing resolved function as param without first id
			if ( count( $resFct ) > 1 ) {
				$shifted = array_shift( $resFct );
				$parsed = BaseParsing::{$shifted}( $node, $passedArgs, $operatorContent, $input, ...$resFct );
				if ( is_array( $parsed ) ) {
					return implode( $parsed );
				}
				return (string)$parsed;
			} else {
				$parsed = BaseParsing::{$resFct[0]}( $node, $passedArgs, $operatorContent, $input );
				if ( is_array( $parsed ) ) {
					return implode( $parsed );
				}
				return (string)$parsed;
			}
		} catch ( \Exception $exception ) {
			return null;
		}
	}

	public function checkAndParseOperator( $input, $node, $passedArgs, $operatorContent,
										   $state, $prepareInput = true ) {
			$resOperator = TexUtil::getInstance()->operator_rendering( trim( $input ) );
		if ( $resOperator == null ) {
			$resOperator = TexUtil::getInstance()->operator_infix( trim( $input ) );
			if ( $resOperator ) {
				if ( isset( $resOperator[1] ) ) {
					// custom parsing here
					return $this->parseOperatorDict( $node, $passedArgs, $operatorContent, $input, false );
				}
				// Atm just do simple parsing for elements in operator dictionary
				return (string)new MMLmo( '', $passedArgs, $input );
			}
		}

		// If the macro has been found, dynamically call the associated parsing function.
		if ( is_string( $resOperator ) ) {
			$resOperator = [ $resOperator ];
		}

		if ( $resOperator == null ) {
			return null;
		}
		try {
			return $this->parseOperator( $node, $passedArgs, $operatorContent, $input, $state, ...$resOperator );

		} catch ( ArgumentCountError $errArgcount ) {
			return null;
		}
	}

	public function parseOperatorDict( $node, $passedArgs, $operatorContent, $input, $uc = null, $attrs = [] ) {
		// Some custom parsing from operatorDict
		switch ( $input ) {
			case ";":
			case ",":
				// this maybe just a default case, this is not rendered when it is the last in row
				return (string)( new MMLmo( "", [], $input ) );
			case "<":
				return (string)( new MMLmo( "", [], "<" ) );
			case ">":
				return (string)( new MMLmo( "", [], ">" ) );
			case "\\":
				 // instead of carriage return, force whitespace here:
				 // see: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Math/+/961213
				return (string)new MMLmspace( "", [ "width" => "0.5em" ] );
			case '/':
				return (string)new MMLmo( '', [ 'lspace' => '0', 'rspace' => '0' ], $input );
		}
		return $input;
	}

	public function parseOperator( $node, $passedArgs, $operatorContent, $name, $state, $uc = null, $attrs = [] ) {
		// if($name == "equiv" || $name == "dotplus" || $name == "mp"  || $name == "pm"){
		$attrs = array_merge( $passedArgs, $attrs ); // this is rather a workaround
		if ( array_key_exists( "largeop", $attrs ) && $attrs['largeop'] == "" ) {
			unset( $attrs['largeop'] );
		}
		if ( array_key_exists( "movesupsub", $attrs ) && $attrs['movesupsub'] == "1" ) {
			unset( $attrs['movesupsub'] );
		}

		if ( $state != null && array_key_exists( "not", $state ) && $state["not"] ) {
			return (string)new MMLmo( "", $attrs, $uc . "&#x338;" );
		}
		return (string)new MMLmo( "", $attrs, $uc );
	}

	public function checkAndParseIdentifier( $input, $node, $passedArgs, $operatorContent, $prepareInput = true ) {
		// @phan-suppress-next-line PhanCoalescingNeverUndefined
		$resIdentifier = TexUtil::getInstance()->identifier( trim( $input ) ) ?? null;
		// If the macro has been found, dynamically call the associated parsing function.
		if ( is_string( $resIdentifier ) ) {
			$resIdentifier = [ $resIdentifier ];
		}

		if ( $resIdentifier == null ) {
			return null;
		}
		try {
			$resIdentifier[0] = MMLutil::uc2xNotation( $resIdentifier[0] );
			return $this->parseIdentifier( $node, $passedArgs, $operatorContent, $input, ...$resIdentifier );
		} catch ( ArgumentCountError $errArgcount ) {
			return null;
		}
	}

	public function parseIdentifier( $node, $passedArgs, $operatorContent, $name, $uc = null, $attrs = [] ) {
		// tbd verify rule: Lowercase name ("operator" instead "Operator") seems to
		// indicate additional italic mathvariant when bold already
		if ( !ctype_upper( $name ) ) {
			if ( isset( $passedArgs["mathvariant"] ) && $passedArgs["mathvariant"] === 'bold' ) {
				$passedArgs["mathvariant"] = $passedArgs["mathvariant"] . "-" . Variants::ITALIC;
			}
		}

		$args = array_merge( $passedArgs, $attrs );

		if ( array_key_exists( "texClass", $args ) ) {
			$args["data-mjx-texclass"] = $args["texClass"];
			unset( $args["texClass"] );
		}

		return (string)new MMLmi( "", $args, $uc );
	}

	public function checkAndParseDelimiter( $input, $node, $passedArgs,
											$operatorContent, $noargs = false, $texClass = "" ) {
		if ( $input === null ) {
			return null;
		}
		$input = trim( $input );

		$resDelimiter = TexUtil::getInstance()->delimiter( $input ) ?? false;
		if ( $resDelimiter === false || !is_string( $resDelimiter[0] ) ) {
			return null;
		}

		if ( isset( $resDelimiter[1] ) && is_array( $resDelimiter[1] ) && !$noargs ) {
			$passedArgs = array_merge( $resDelimiter[1], $passedArgs );
		}

		return (string)new MMLmo( $texClass, $passedArgs, $resDelimiter[0] );
	}

	public function checkAndParseMathCharacter( $input, $node, $passedArgs, $operatorContent, $prepareInput = true ) {
		$resChar = TexUtil::getInstance()->mathchar( trim( $input ) );
		if ( $resChar == null ) {
			return null;
		}
		return (string)new MMLmi( "", [ "mathvariant" => "normal" ], $resChar );
	}

	public function checkAndParseColor( $input, $node, $passedArgs, $operatorContent, $prepareInput = true ) {
		// tbd usually this encapsulates the succeeding box element
		if ( $operatorContent == null ) {
			return null;
		}

		if ( !( $input === 'color' || $input === 'pagecolor' ) ) {
			return null;
		}
		$resColor = TexUtil::getInstance()->color( ucfirst( $operatorContent ) );
		if ( $resColor == null ) {
			return null;
		}
		if ( $input === 'color' ) {
			return (string)( new MMLmstyle( "", [ "mathcolor" => $resColor ] ) );
		} else {
			// Input is 'pagecolor'
			$mrow = new MMLmrow();
			// Mj3 does this, probably not necessary
			$innerRow = "";
			foreach ( str_split( $operatorContent ) as $char ) {
				$innerRow .= new MMLmi( "", [], $char );
			}
			if ( $innerRow !== "" ) {
				return ( new MMLmtext( "", [ "mathcolor" => $resColor ], "\\pagecolor" ) ) .
					$mrow->encapsulateRaw( $innerRow );
			} else {
				return (string)( new MMLmtext( "", [ "mathcolor" => $resColor ], "\\pagecolor" ) );
			}
		}
	}

	public static function generateMMLError( string $msg ): string {
		return ( new MMLmerror() )->encapsulateRaw(
			(string)( new MMLmtext( "", [], $msg ) )
		);
	}
}
