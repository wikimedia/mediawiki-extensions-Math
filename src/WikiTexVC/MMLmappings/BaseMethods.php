<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use Exception;
use LogicException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmerror;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;
use MediaWiki\Logger\LoggerFactory;

/**
 * This contains the basic parsing methods for tex elements, which get invoked
 * to check if there is a specific parsing function defined in the mappings
 * and then forward to the parsing function.
 *
 * Much of this is WIP since there are many cases.
 * @author Johannes Stegmüller
 */
class BaseMethods {

	public static function checkAndParse( $input, $passedArgs, $operatorContent, TexNode $node,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		if ( !is_string( $input ) ) {
			// just discard these elements, sometimes empty TexArray
			return new MMLarray();
		}

		// Checking for a named parsing function

		$resFct = TexUtil::getInstance()->callback( trim( $input ) );
		if ( $resFct == null ) {
			return new MMLarray();
		}
		// If the function has been found, dynamically call the associated parsing function.
		if ( is_string( $resFct ) ) {
			$resFct = [ $resFct ];
		}
		if ( str_contains( $resFct[0], '::', ) ) {
			throw new LogicException( "Callback to $resFct[0] should be treated in the respective class." );
		}
		// Passing resolved function as param without first id; the context goes last, by name
		$shifted = array_shift( $resFct );
		$resFct['ctx'] = $ctx;
		try {
			return BaseParsing::{$shifted}( $node, $passedArgs, $operatorContent, $input, ...$resFct );
		} catch ( Exception $e ) {
			LoggerFactory::getInstance( 'Math' )->error( 'Callback {callback} failed for {input}', [
				'callback' => $shifted,
				'input' => $input,
				'exception' => $e,
			] );
			return new MMLarray();
		}
	}

	public function checkAndParseOperator( $input, $node, $passedArgs, $operatorContent,
		$prepareInput = true
	): MMLbase {
		$resOperator = TexUtil::getInstance()->operator_rendering( trim( $input ) );
		if ( $resOperator == null ) {
			$resOperator = TexUtil::getInstance()->operator_infix( trim( $input ) );
			if ( $resOperator ) {
				if ( isset( $resOperator[1] ) ) {
					// custom parsing here
					return $this->parseOperatorDict( $node, $passedArgs, $operatorContent, $input, false );
				}
				// Atm just do simple parsing for elements in operator dictionary
				return new MMLmo( '', $passedArgs, $input );
			}
		}

		// If the macro has been found, dynamically call the associated parsing function.
		if ( is_string( $resOperator ) ) {
			$resOperator = [ $resOperator ];
		}

		if ( $resOperator == null ) {
			return new MMLarray();
		}
		return $this->parseOperator( $node, $passedArgs, $operatorContent, $input, ...$resOperator );
	}

	public function parseOperatorDict( $node, $passedArgs, $operatorContent, $input, $uc = null,
		$attrs = []
	): MMLbase {
		// Some custom parsing from operatorDict
		switch ( $input ) {
			// effectively, those operations are not tagged with stretchy=false
			case ";":
			case ",":
			case "<":
			case ">":
				// this maybe just a default case, this is not rendered when it is the last in row
				return new MMLmo( "", [], $input );
			case "\\":
				 // instead of carriage return, force whitespace here:
				 // see: https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Math/+/961213
				return new MMLmspace( "", [ "width" => "0.5em" ] );
			case '/':
				return new MMLmo( '', [ 'lspace' => '0', 'rspace' => '0' ], $input );
		}
		throw new LogicException( "$input is not a valid operator." );
	}

	public function parseOperator( $node, $passedArgs, $operatorContent, $name, $uc = null,
		$attrs = []
	): MMLbase {
		// if($name == "equiv" || $name == "dotplus" || $name == "mp"  || $name == "pm"){
		$attrs = array_merge( $passedArgs, $attrs ); // this is rather a workaround
		if ( array_key_exists( "largeop", $attrs ) && $attrs['largeop'] == "" ) {
			unset( $attrs['largeop'] );
		}
		return new MMLmo( trim( $name ) === '\\colon' ? 'PUNCT' : '', $attrs, $uc );
	}

	public function checkAndParseDelimiter( $input, $node, $passedArgs,
		$operatorContent, $noargs = false, $texClass = "" ): MMLbase {
		if ( $input === null ) {
			return new MMLarray();
		}
		$input = trim( $input );

		$resDelimiter = TexUtil::getInstance()->delimiter( $input ) ?? false;
		if ( $resDelimiter === false || !is_string( $resDelimiter[0] ) ) {
			return new MMLarray();
		}

		if ( isset( $resDelimiter[1] ) && is_array( $resDelimiter[1] ) && !$noargs ) {
			$passedArgs = array_merge( $resDelimiter[1], $passedArgs );
		}

		return new MMLmo( $texClass, $passedArgs, $resDelimiter[0] );
	}

	public function checkAndParseMathCharacter( $input, $node, $passedArgs, $operatorContent,
		$prepareInput = true
	): MMLbase {
		$resChar = TexUtil::getInstance()->mathchar( trim( $input ) );
		if ( $resChar == null ) {
			return new MMLarray();
		}
		return new MMLmi( '', [ 'mathvariant' => Variants::NORMAL ], $resChar );
	}

	public static function generateMMLError( string $msg ): MMLmerror {
		return new MMLmerror( "", [], new MMLmtext( "", [], $msg ) );
	}
}
