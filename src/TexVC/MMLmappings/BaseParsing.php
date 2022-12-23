<?php
namespace MediaWiki\Extension\Math\TexVC\MMLmappings;

use IntlChar;
use MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants\Misc;
use MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\TexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmenclose;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmfrac;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmmultiscripts;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmphantom;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmroot;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmsqrt;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmsub;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmsubsup;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmsup;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmtable;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmtd;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmtr;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmunder;
use MediaWiki\Extension\Math\TexVC\MMLnodes\MMLmunderover;
use MediaWiki\Extension\Math\TexVC\Nodes\Curly;
use MediaWiki\Extension\Math\TexVC\Nodes\DQ;
use MediaWiki\Extension\Math\TexVC\Nodes\Fun1nb;
use MediaWiki\Extension\Math\TexVC\Nodes\Fun2sq;
use MediaWiki\Extension\Math\TexVC\Nodes\Literal;
use MediaWiki\Extension\Math\TexVC\Nodes\TexArray;

/**
 * Parsing functions for specific recognized mappings.
 * Usually the parsing functions are invoked from the BaseMethods classes.
 */
class BaseParsing {

	public static function accent( $node, $passedArgs, $name, $operatorContent, $accent, $stretchy = null ) {
		// Currently this is own implementation from Fun1.php
		// TODO The first if-clause is mathjax specific (and not necessary by generic parsers)
		// and will most probably removed (just for running all tc atm)
		if ( $accent == "00B4" || $accent == "0060" ) {
			$attrs = [ Tag::SCRIPTTAG => "true" ];
		} else {
			if ( $stretchy == null ) {
				// $attrs = [ "stretchy" => "false" ]; // not mention explicit stretchy
				$attrs = [];
			} else {
				$attrs = [ "stretchy" => "true" ];
			}
		}
		// Fetching entity from $accent key tbd
		$entity = MMLutil::createEntity( $accent );
		if ( !$entity ) {
			$entity = $accent;
		}

		if ( $node->getArg() instanceof Curly && $node->getArg()->getArg() instanceof TexArray
			&& count( $node->getArg()->getArg()->getArgs() ) > 1 ) {
			$mrow = new MMLmrow();
			$renderedArg = $mrow->encapsulate( $node->getArg()->renderMML() );
		} else {
			$renderedArg = $node->getArg()->renderMML();
		}

		$mrow = new MMLmrow();
		$mo = new MMLmo( "", $attrs ); // $passedArgs
		$mover = new MMLmover();
		$ret = $mrow->encapsulate(
			$mrow->encapsulate(
				$mover->encapsulate(
					$renderedArg .
					$mo->encapsulate( $entity )
				)
			)
		);
		return $ret;
	}

	public static function array( $node, $passedArgs, $operatorContent, $name, $begin = null, $open = null,
								  $close = null, $align = null, $spacing = null,
								  $vspacing = null, $style = null, $raggedHeight = null ) {
		$output = "";
		$mrow = new MMLmrow();
		if ( $open != null ) {
			$resDelimiter = BaseMappings::getDelimiterByKey( trim( $open ) );
			if ( $resDelimiter ) {
				// $retDelim = $bm->checkAndParseDelimiter($open, $node,$passedArgs,true);
				$moOpen = new MMLmo( TexClass::OPEN );
				$output .= $moOpen->encapsulate( $resDelimiter[0] );
			}
		}
		if ( $name == "Bmatrix" || $name == "bmatrix" || $name == "Vmatrix"
			|| $name == "vmatrix" || $name == "smallmatrix" || $name == "pmatrix" || $name == "matrix" ) {
			// This is a workaround and might be improved mapping BMatrix to Matrix directly instead of array
			return self::matrix( $node, $passedArgs, $operatorContent, $name,
				$open, $close, null, null, null, null, true );

		} else {
			$output .= $mrow->encapsulate( $node->getMainarg()->renderMML() );
		}

		if ( $close != null ) {
			$resDelimiter = BaseMappings::getDelimiterByKey( trim( $close ) );
			if ( $resDelimiter ) {
				// $retDelim = $bm->checkAndParseDelimiter($open, $node,$passedArgs,true);
				$moClose = new MMLmo( TexClass::CLOSE );
				$output .= $moClose->encapsulate( $resDelimiter[0] );
			}
		}
		return $output;
	}

	public static function alignAt( $node, $passedArgs, $operatorContent, $name, $smth, $smth2 = null ) {
		// Parsing is very similar to AmsEQArray, maybe extract function ... tcs: 178
		$mrow = new MMLmrow();
		// tbd how are the table args composed ?
		$tableArgs = [ "columnalign" => "right",
			"columnspacing" => "", "displaystyle" => "true", "rowspacing" => "3pt" ];
		$mtable  = new MMLmtable( "", $tableArgs );
		$mtr = new MMLmtr();
		$mtd = new MMLmtd();
		$renderedInner = "";

		$tableElements = array_slice( $node->getArgs(), 1 )[0];
		$discarded = false;
		foreach ( $tableElements->getArgs() as $tableRow ) {
			$renderedInner .= $mtr->getStart();
			foreach ( $tableRow->getArgs() as $tableCell ) {
				$renderedInner .= $mtd->getStart();
				foreach ( $tableCell->getArgs() as $cellItem ) {
					if ( !$discarded && $cellItem instanceof Curly ) {
						$discarded = true;
						// Just discard the number of rows atm, it is in the first Curly
					} else {
						$renderedInner .= $cellItem->renderMML(); // pass args here ?
					}
				}

				$renderedInner .= $mtd->getEnd();

			}
			$renderedInner .= $mtr->getEnd();
		}
		return $mrow->encapsulate( $mtable->encapsulate( $renderedInner ) );
	}

	public static function amsEqnArray( $node, $passedArgs, $operatorContent, $name, $smth, $smth2 = null ) {
		// this goes for name =="aligned" ... tcs: 358 420 421
		$mrow = new MMLmrow();
		// tbd how are the table args composed ?
		$tableArgs = [ "columnalign" => "right",
			"columnspacing" => "", "displaystyle" => "true", "rowspacing" => "3pt" ];
		$mtable  = new MMLmtable( "", $tableArgs );
		$mtr = new MMLmtr();
		$mtd = new MMLmtd();
		$renderedInner = "";
		$tableElements = array_slice( $node->getArgs(), 1 )[0];
		foreach ( $tableElements->getArgs() as $tableRow ) {
			$renderedInner .= $mtr->getStart();
			foreach ( $tableRow->getArgs() as $tableCell ) {
				$renderedInner .= $mtd->encapsulate( $tableCell->renderMML() ); // pass args here ?
			}
			$renderedInner .= $mtr->getEnd();
		}
		return $mrow->encapsulate( $mtable->encapsulate( $renderedInner ) );
	}

	public static function boldsymbol( $node, $passedArgs, $operatorContent, $name, $smth = null, $smth2 = null ) {
		$mrow = new MMLmrow();
		$passedArgs = array_merge( [ "mathvariant" => Variants::BOLDITALIC ] );
		return $mrow->encapsulate( $node->getArg()->renderMML( $passedArgs ) );
	}

	public static function cancel( $node, $passedArgs, $operatorContent, $name, $notation = null, $smth2 = null ) {
		$mrow = new MMLmrow();
		$menclose = new MMLmenclose( "", [ "notation" => $notation ] );
		return $mrow->encapsulate( $menclose->encapsulate( $node->getArg()->renderMML() ) );
	}

	public static function cancelTo( $node, $passedArgs, $operatorContent, $name, $notation = null ) {
		$mrow = new MMLmrow();
		$msup = new MMLmsup();
		$mpAdded = new MMLmpadded( "", [ "depth" => "-.1em" ,"height" => "+.1em" ,"voffset" => ".1em" ] );

		$menclose = new MMLmenclose( "", [ "notation" => $notation ] );
		$inner = $menclose->encapsulate(
			$node->getArg2()->renderMML() ) . $mpAdded->encapsulate( $node->getArg1()->renderMML() );
		return $mrow->encapsulate( $msup->encapsulate( $inner ) );
	}

	public static function chemCustom( $node, $passedArgs, $operatorContent, $name, $translation = null ) {
		if ( $translation ) {
			return $translation;
		}
		return "tbd chemCustom";
	}

	public static function cFrac( $node, $passedArgs, $operatorContent, $name ) {
		$mrow = new MMLmrow();
		$mfrac = new MMLmfrac();
		$mstyle = new MMLmstyle( "",  [ "displaystyle" => "false", "scriptlevel" => "0" ] );
		$mpAdded = new MMLmpadded( "", [ "depth" => "3pt","height" => "8.6pt","width" => "0" ] );
		// See TexUtilMMLTest testcase 81
		// (mml3 might be erronous here, but this element seems to be rendered correctly)
		$whatIsThis = $mrow->getStart() . $mpAdded->getStart() . $mpAdded->getEnd() . $mrow->getEnd();
		$inner = $mrow->encapsulate( $whatIsThis .
				$mstyle->encapsulate( $mrow->encapsulate( $node->getArg1()->renderMML() ) ) ) .
			$mrow->encapsulate( $whatIsThis . $mstyle->encapsulate(
				$mrow->encapsulate( $node->getArg2()->renderMML() ) ) );

		return $mrow->encapsulate( $mfrac->encapsulate( $inner ) );
	}

	public static function dots( $node, $passedArgs, $operatorContent, $name, $smth = null, $smth2 = null ) {
		// lowerdots || centerdots seems aesthetical, just using lowerdots atm s
		$mo = new MMLmo( "", $passedArgs );
		return $mo->encapsulate( "&#x2026;" );
	}

	public function genFrac( $node, $passedArgs, $name, $operatorContent,
							 $left = null, $right = null, $thick = null, $style = null ) {
		// Actually this is in AMSMethods, consider refactoring  left, right, thick, style
		$bm = new BaseMethods();
		$ret = $bm->checkAndParseDelimiter( $name, $node, $passedArgs, $operatorContent, true );
		if ( $ret ) {
			// TBD
			if ( $left == null ) {
				$left = $ret;
			}
			if ( $right == null ) {
				$right = $ret;
			}
			if ( $thick == null ) {
				$thick = $ret;
			}
			if ( $style == null ) {
				$style = trim( $ret );
			}
		}
		$attrs = [];
		$fract = null;
		$styleAttr = [];
		$displayStyle = "false";
		if ( $thick !== "" ) {
			$attrs = array_merge( $attrs, [ "linethickness" => $thick ] );
		}
		if ( $style !== '' ) {
			$styleDigit = intval( $style, 10 );
			$styleAlpha = [ 'D', 'T', 'S', 'SS' ][$styleDigit];
			if ( $styleAlpha == null ) {
				$mrow = new MMLmrow();
				return $mrow->encapsulate( "Bad math style" );
			}

			if ( $styleAlpha === 'D' ) {
				// NodeUtil_js_1.default.setProperties(frac, { displaystyle: true, scriptlevel: 0 });

				// tbd add props
				$displayStyle = "true";
				$styleAttr = [ "maxsize" => "2.047em", "minsize" => "2.047em" ];

			} else {
				$styleAttr = [ "maxsize" => "1.2em", "minsize" => "1.2em" ];
			}

			$frac = new MMLmfrac( "", $attrs );
		} else {
			// NodeUtil_js_1.default.setProperties(frac, { displaystyle: false,
			//    scriptlevel: styleDigit - 1 });
			// tbd add props
			$frac = new MMLmfrac( "", $attrs );
			$styleAttr = [ "maxsize" => "1.2em", "minsize" => "1.2em" ];

		}
		$mrow = new MMLmrow();
		$mstyle = new MMLmstyle( "", [ "displaystyle" => $displayStyle, "scriptlevel" => "0" ] );
		$output = $mrow->getStart();
		if ( $style !== '' ) {
			$output .= $mstyle->getStart();
		}
		$output .= $mrow->getStart();
		if ( $left ) {
			$mrowOpen = new MMLmrow( TexClass::OPEN );
			$moL = new MMLmo( "", $styleAttr );
			$output .= $mrowOpen->encapsulate( $moL->encapsulate( $left ) );
		}
		// when is mrow encapsulating and when not ?
		//$output .= $frac->encapsulate(
		// $mrow->encapsulate($node->getArg1()->renderMML()) .$mrow->encapsulate( $node->getArg2()->renderMML() ));
		$output .= $frac->encapsulate( $node->getArg1()->renderMML() . $node->getArg2()->renderMML() );

		if ( $right ) {
			$mrowClose = new MMLmrow( TexClass::CLOSE );
			$moR = new MMLmo( "", $styleAttr );
			$output .= $mrowClose->encapsulate( $moR->encapsulate( $right ) );

		}
		$output .= $mrow->getEnd();
		if ( $style !== '' ) {
			$output .= $mstyle->getEnd();
		}
		$output .= $mrow->getEnd();

		return $output;
	}

	public static function frac( $node, $passedArgs, $operatorContent, $name ) {
		$mrow = new MMLmrow();
		$mfrac = new MMLmfrac();
		// if node is Fun1
		$inner = $mrow->encapsulate( $node->getArg1()->renderMML() ) .
			$mrow->encapsulate( $node->getArg2()->renderMML() );
		return $mrow->encapsulate( $mfrac->encapsulate( $inner ) );
	}

	public static function hline( $node, $passedArgs, $operatorContent, $name,
								  $smth1 = null, $smth2 = null, $smth3 = null, $smth4 = null ) {
		// HLine is most probably not parsed this way, since only parsed in Matrix context
		$mmlRow = new MMLmrow( "tbd" );
		return $mmlRow->encapsulate( "HLINE TBD" );
	}

	public static function handleOperatorName( $node, $passedArgs, $operatorContent, $name,
											   $smth1 = null, $smth2 = null, $smth3 = null, $smth4 = null ) {
		// \\operatorname{a}
		$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::OP, "mathvariant" => Variants::NORMAL ] );
		return $node->getArg()->renderMML( $passedArgs );
	}

	public static function macro( $node, $passedArgs, $operatorContent, $name, $macro, $argcount = null, $def = null ) {
		// Parse the Macro
		switch ( $name ) {
			case "mod":
				$mmlRow = new MMLmrow();
				$mspace = new MMLmspace( "", [ "width" => "0.444em" ] );
				$mspace2 = new MMLmspace( "", [ "width" => "0.333em" ] );
				$mo = new MMLmo( "", [ "stretchy" => "false" ] );
				$mi = new MMLmi();
				return $mmlRow->encapsulate(
					$mspace->encapsulate( "" ) . $mo->encapsulate( "(" ) .
					$mi->encapsulate( "mod" ) . $mspace2->encapsulate( "" ) .
					$mo->encapsulate( ")" ) );
			case "pmod":
				// tbd indicate in mapping that this is composed within php
				$mmlRow = new MMLmrow();
				$mspace = new MMLmspace( "", [ "width" => "0.444em" ] );
				$mspace2 = new MMLmspace( "", [ "width" => "0.333em" ] );
				$mo = new MMLmo( "", [ "stretchy" => "false" ] );
				$mi = new MMLmi();
				return $mmlRow->encapsulate( $mspace->encapsulate( "" ) .
					$mo->encapsulate( "(" ) . $mi->encapsulate( "mod" ) .
					$mspace2->encapsulate( "" ) . $node->getArg()->renderMML() . $mo->encapsulate( ")" ) );
			case "varlimsup":
			case "varliminf":
				// hardcoded macro in php (there is also a dynamic mapping which is not completely resolved atm)
				$mmlRow = new MMLmrow( TexClass::OP );
				if ( $name === "varlimsup" ) {
					$movu = new MMLmover();

				} else {
					$movu = new MMLmunder();
				}
				$mmlMi = new MMLmi();
				$mo = new MMLmo( "", [ "accent" => "true" ] );
				return $mmlRow->encapsulate( $movu->encapsulate(
					$mmlMi->encapsulate( "lim" ) . $mo->encapsulate( "&#x2015;" ) ) );

			case "varinjlim":
				$mmlRow = new MMLmrow( TexClass::OP );
				$mmlMunder = new MMLmunder();
				$mi = new MMLmi();
				$mo = new MMLmo();
				return $mmlRow->encapsulate( $mmlMunder->encapsulate(
					$mi->encapsulate( "lim" ) .
					$mo->encapsulate( "&#x2192;" ) )
				);
			case "varprojlim":
				$mmlRow = new MMLmrow( TexClass::OP );
				$mmlMunder = new MMLmunder();
				$mi = new MMLmi();
				$mo = new MMLmo();
				return $mmlRow->encapsulate( $mmlMunder->encapsulate(
					$mi->encapsulate( "lim" ) .
					$mo->encapsulate( "&#x2190;" )
				) );
			case "stackrel":
				// hardcoded macro in php (there is also a dynamic mapping which is not not completely resolved atm)
				$mmlRow = new MMLmrow();
				$mmlRowInner = new MMLmrow( TexClass::REL );
				$mover = new MMLmover();
				$mmlRowArg2 = new MMLmrow( TexClass::OP );
				$inner = $mover->encapsulate( $mmlRowArg2->encapsulate(
						$node->getArg2()->renderMML() ) .
					$mmlRow->encapsulate( $node->getArg1()->renderMML() )
				);
				return $mmlRow->encapsulate( $mmlRowInner->encapsulate( $inner ) );
			case "bmod":
				$mo = new MMLmo( "", [ "lspace" => "thickmathspace", "rspace" => "thickmathspace" ] );
				$mmlRow = new MMLmrow( TexClass::ORD );
				$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
				$mspace = new MMLmspace( "", [ "width" => "0.167em" ] );
				return $mmlRow->encapsulate( $mo->encapsulate( "mod" ) .
					$mmlRow->encapsulate( $mstyle->encapsulate( $mspace->getEmpty() ) ) );
			case "implies":
				$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
				$mspace = new MMLmspace( "", [ "width" => "0.278em" ] );
				$mo = new MMLmo();
				return $mstyle->encapsulate( $mspace->getEmpty() ) . $mo->encapsulate( "&#x27F9;" ) .
					$mstyle->encapsulate( $mspace->getEmpty() );
			case "iff":
				$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
				$mspace = new MMLmspace( "", [ "width" => "0.278em" ] );
				$mo = new MMLmo();
				return $mstyle->encapsulate( $mspace->getEmpty() ) . $mo->encapsulate( "&#x27FA;" ) .
					$mstyle->encapsulate( $mspace->getEmpty() );
		}

		// Removed all token based parsing, since macro resolution for the supported macros can be hardcoded in php
		$mmlMrow = new MMLmrow();
		return $mmlMrow->encapsulate( "macro not resolved: " . $macro );
	}

	public static function matrix( $node, $passedArgs, $operatorContent,
								   $name, $open = null, $close = null, $align = null, $spacing = null,
								   $vspacing = null, $style = null, $cases = null, $numbered = null ) {
		$resInner = "";
		$mtr = new MMLmtr();
		$mtd = new MMLmtd();
		$addHlines = false;
		$columnInfo = [];
		// tbd hline element is the first literal element within second texarray -> resolve
		foreach ( $node->getMainarg()->getArgs() as $mainarg ) {
			$resInner .= $mtr->getStart();
			foreach ( $mainarg->getArgs() as $arg ) {
				$usedArg = clone $arg;
				if ( count( $arg->getArgs() ) >= 1 && $arg->getArgs()[0] instanceof Literal ) {
					// Discarding the column information Curly at the moment
					if ( $arg->getArgs()[0]->getArg() == "\\hline " ) {
						// discarding the hline
						// $usedArg->args[0] = null; // this does no work tbd
						$usedArg->pop();
						$addHlines = true;
					}
				}
				if ( count( $arg->getArgs() ) >= 1 && $arg->getArgs()[0] instanceof Curly ) {
					// Discarding the column information Curly at the moment
					// $usedArg->getArgs()[0] = null;
					$columnInfo = $usedArg->getArgs()[0]->render();
					$usedArg->pop();

				}
				$resInner .= $mtd->encapsulate( $usedArg->renderMML( $passedArgs ) );
			}
			$resInner .= $mtr->getEnd();
		}
		$mrow = new MMLmrow();
		$tableArgs = [ "columnspacing" => "1em", "rowspacing" => "4pt" ];
		$mencloseArgs = null;
		if ( $addHlines ) {
			// TBD this is just simple check, create a parsing function for hlines when there are more cases
			// solid as first val: hline for header row
			// none as second val: no hlines for follow up rows
			$tableArgs = array_merge( $tableArgs, [ "rowlines" => "solid none" ] );
		}
		if ( $columnInfo ) {
			// TBD this is just simple check, create a parsing function for hlines when there are more cases
			if ( str_contains( $columnInfo, "|" ) ) {
				$mencloseArgs = [ "data-padding" => "0","notation" => "left right" ];
				// it seems this is creted when left and right is solely coming from columninfo
				$tableArgs = array_merge( $tableArgs, [ "columnlines" => "solid" ] );
			}
		}
		$mtable = new MMLmtable( "",  $tableArgs );
		if ( $cases || ( $open != null && $close != null ) ) {
			$bm = new BaseMethods();
			$mmlMoOpen = $bm->checkAndParseDelimiter( $open, $node, [], [],
				true, TexClass::OPEN );
			if ( $mmlMoOpen == null ) {
				$open = MMLutil::inputPreparation( $open );
				$mmlMoOpen = new MMLmo( TexClass::OPEN, [] );
				$mmlMoOpen = $mmlMoOpen->encapsulate( $open );
			}

			$closeAtts = [ "fence" => "true","stretchy" => "true","symmetric" => "true" ];
			$mmlMoClose = $bm->checkAndParseDelimiter( $close, $node, $closeAtts,
				null, true, TexClass::CLOSE );
			if ( $mmlMoOpen == null ) {
				$close = MMLutil::inputPreparation( $close );
				$mmlMoClose = new MMLmo( TexClass::CLOSE, $closeAtts );
				$mmlMoClose = $mmlMoClose->encapsulate( $close );
			}
			$resInner = $mmlMoOpen . $mtable->encapsulate( $resInner ) . $mmlMoClose;
		} else {
			$resInner = $mtable->encapsulate( $resInner );
		}
		if ( $mencloseArgs ) {
			$menclose = new MMLmenclose( "", $mencloseArgs );
			$matrix = $mrow->encapsulate( $menclose->encapsulate( $resInner ) );

		} else {
			$matrix = $mrow->encapsulate( $resInner );
		}
		return $matrix;
	}

	public static function namedOp( $node, $passedArgs, $operatorContent, $name, $id = null ) {
		if ( !$id ) {
			// $id = substr($name,1); eventually change how name is passed
			$id = $name;
		}
		// This id statement probably wont work atm:

		// lim&#x2006;inf
		$id = str_replace( "&thinsp;", '&#x2006;', $id );
		$mo = new MMLmo( TexClass::OP, [ "movablelimits" => "true" ] );
		// "movesupsub"=>"true" activate this also as attribute ?
		return $mo->encapsulate( $id );
	}

	public static function over( $node, $passedArgs, $operatorContent, $name, $id = null ) {
		$attributes = [];
		$start = "";
		$tail = "";
		if ( $name === "atop" ) {
			$attributes = [ "linethickness" => "0" ];
		} elseif ( $name == "choose" ) {
			$mrowAll = new MMLmrow( TexClass::ORD );
			$mrowOpen = new MMLmrow( TexClass::OPEN );
			$mrowClose = new MMLmrow( TexClass::CLOSE );
			$mo = new MMLmo( "", [ "maxsize" => "1.2em","minsize" => "1.2em" ] );
			$start = $mrowAll->getStart() . $mrowOpen->encapsulate( $mo->encapsulate( "(" ) );
			$tail = $mrowClose->encapsulate( $mo->encapsulate( ")" ) ) . $mrowAll->getEnd();
			$attributes = [ "linethickness" => "0" ];

		}
		$mfrac = new MMLmfrac( "", $attributes ); // "movesupsub"=>"true" activate this also as attribute ?

		$mrow = new MMLmrow( "", [] ); // tbd remove mathjax specifics,
		// tbd added a getArg2 mrow which seems correct, consider removiing in some cases ?
		return $start . $mfrac->encapsulate( $mrow->encapsulate(
			$node->getArg1()->renderMML() ) . $mrow->encapsulate( $node->getArg2()->renderMML() ) ) . $tail;
	}

	public static function oint( $node, $passedArgs, $operatorContent,
								 $name, $uc = null, $smth1 = null, $smth2 = null ) {
		// This is a custom mapping not in js.
		$mmlText = new MMLmtext( "", [ "mathcolor" => "red" ] );
		switch ( $name ) {
			case "oiiint":
			case "oiint":
			case "ointctrclockwise":
			case "varointclockwise":
			case "P":
				return $mmlText->encapsulate( "\\" . $name );
			default:
				return $mmlText->encapsulate( "not found in OintMethod" );

		}
	}

	public static function overset( $node, $passedArgs, $operatorContent, $name, $id = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] ); // tbd remove mathjax specifics
		$mrow2 = new MMLmrow( "", [] );
		$inrow = $mrow2->encapsulate( $node->getArg2()->renderMML() );
		$mover = new MMLmover();
		return $mrow->encapsulate( $mover->encapsulate( $inrow . $node->getArg1()->renderMML() ) );
	}

	public static function phantom( $node, $passedArgs, $operatorContent,
									$name, $vertical = null, $horizontal = null, $smh3 = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] );

		$attrs = [];
		if ( $vertical ) {
			$attrs = array_merge( $attrs, [ "width" => "0" ] );
		}
		if ( $horizontal ) {
			$attrs = array_merge( $attrs, [ "depth" => "0", "height" => "0" ] );
		}
		$mpadded = new MMLmpadded( "", $attrs );
		$mphantom = new MMLmphantom();
		return $mrow->encapsulate( $mrow->encapsulate(
			$mpadded->encapsulate( $mphantom->encapsulate( $node->getArg()->renderMML() ) ) ) );
	}

	public static function underset( $node, $passedArgs, $operatorContent, $name, $smh = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] ); // tbd remove mathjax specifics
		$mrow2 = new MMLmrow( "", [] );
		$inrow = $node->getArg2()->renderMML();
		$munder = new MMLmunder();

		// Some cases encapsulate getArg1 in Mrow ??
		return $mrow->encapsulate( $munder->encapsulate( $inrow . $node->getArg1()->renderMML() ) );
	}

	public static function underOver( $node, $passedArgs, $operatorContent,
									  $name, $operatorId = null, $stack = null ) {
		// tbd verify if stack interpreted correctly ?
		$texClass = $stack ? TexClass::OP : TexClass::ORD; // ORD or ""

		$mrow = new MMLmrow( $texClass );

		if ( $name[0] === 'o' ) {
			$movun = new MMLmover();
		} else {
			$movun = new MMLmunder();
		}

		if ( $operatorId == 2015 ) { // eventually move such cases to mapping
			$mo = new MMLmo( "", [ "accent" => "true" ] );
		} else {
			$mo = new MMLmo();
		}
		if ( $node instanceof DQ ) {
			$mrowI = new MMLmrow();
			return $movun->encapsulate(
				$node->getBase()->renderMML() .
				$mrowI->encapsulate( $node->getDown()->renderMML() )
			);
		}

		// TBD: Export this check to utility function it seems to be used multiple times
		$renderedArg = "";
		$check = method_exists( $node, "getArg" ); // this was to prevent crash if DQ, might be refactored
		if ( $check ) {
			if ( $node->getArg() instanceof Curly && $node->getArg()->getArg() instanceof TexArray
				&& count( $node->getArg()->getArg()->getArgs() ) > 1 ) {
				$mrowI = new MMLmrow();
				$renderedArg = $mrowI->encapsulate( $node->getArg()->renderMML() );
			} else {
				$renderedArg = $node->getArg()->renderMML();
			}
		}

		return $mrow->encapsulate( $movun->encapsulate(
			$renderedArg . $mo->encapsulate( MMLutil::number2xNotation( $operatorId ) )
		) );
	}

	public static function mathFont( $node, $passedArgs, $operatorContent, $name, $mathvariant = null ) {
		$mrow = new MMLmrow( TexClass::ORD, [] );
		$mi = new MMLmi();
		$args = MMLParsingUtil::getFontArgs( $name, $mathvariant, $passedArgs );

		if ( $node instanceof Fun1nb ) {
			// Only one mrow from Fun1nb !?
			return $mrow->encapsulate( $node->getArg()->renderMML( $args ) );
		}
		return $mrow->encapsulate( $mrow->encapsulate( $node->getArg()->renderMML( $args ) ) );
	}

	public static function makeBig( $node, $passedArgs, $operatorContent, $name, $texClass = null, $size = null ) {
		// Create the em format and shorten commas
		$size *= Misc::P_HEIGHT;
		$sizeShortened = MMLutil::size2em( strval( $size ) );
		$mrowOuter = new MMLmrow( TexClass::ORD, [] );
		$mrow = new MMLmrow( $texClass, [] );
		$passedArgs = array_merge( $passedArgs, [ "maxsize" => $sizeShortened, "minsize" => $sizeShortened ] );

		$mo = new MMLmo( "", $passedArgs );

		// Sieve arg if it is a delimiter (it seems args are not applied here
		$bm = new BaseMethods();
		$argcurrent = trim( $node->getArg() );
		switch ( $argcurrent ) {
			case "\\|":
				$passedArgs = array_merge( $passedArgs, [ "symmetric" => "true" ] );
				break;
			case "\\uparrow":
			case "\\downarrow":
			case "\\Uparrow":
			case "\\Downarrow":
			case "\\updownarrow":
			case "\\Updownarrow":
				$passedArgs = array_merge( [ "fence" => "true" ], $passedArgs, [ "symmetric" => "true" ] );
				break;
			case "\\backslash":
			case "/":
				$passedArgs = array_merge( [ "fence" => "true" ],
					$passedArgs, [ "stretchy" => "true", "symmetric" => "true" ] );
				break;
		}
		$ret = $bm->checkAndParseDelimiter( $node->getArg(), $node, $passedArgs, $operatorContent, true );
		if ( $ret ) {
			return $mrowOuter->encapsulate( $mrow->encapsulate( $ret ) );
		}

		$argPrep = MMLutil::inputPreparation( $node->getArg() );
		return $mrowOuter->encapsulate( $mrow->encapsulate( $mo->encapsulate( $argPrep ) ) );
	}

	public static function machine( $node, $passedArgs, $operatorContent, $name, $type = null ) {
		// this could also be shifted to MhChem.php renderMML for ce
		// For parsing chem (ce) or ??? (pu)
		$mmlMrow = new MMLmrow();
		return $mmlMrow->encapsulate( $node->getArg()->renderMML() );
	}

	public static function namedFn( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		if ( $node instanceof Literal ) {
			$mi = new MMLmi();
			return $mi->encapsulate( $name );
		}
		$mrow = new MMLmrow( TexClass::ORD, [] ); // tbd remove mathjax specifics
		$msub = new MMLmsub();
		return $msub->encapsulate( $node->getBase()->renderMML() .
			$mrow->encapsulate( $node->getDown()->renderMML() ) );
	}

	public static function limits( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		// not completely done, has preceding lits
		$mrow = new MMLmrow( TexClass::ORD, [] ); // tbd remove mathjax specifics
		$munder = new MMLmunder();

		if ( $node instanceof Literal ) {
			// Workaround currently
			return $munder->encapsulate( $mrow->encapsulate( $node->getArg() ) );

		}
		return $munder->encapsulate( $mrow->encapsulate( $node->getDown()->renderMML() ) );
	}

	public static function setFont( $node, $passedArgs, $operatorContent, $name, $variant = null ) {
		$mrow = new MMLmrow();
		$args = MMLParsingUtil::getFontArgs( $name, $variant, $passedArgs );
		return $mrow->encapsulate( $mrow->encapsulate( $node->getArg()->renderMML( $args ) ) );
	}

	public static function sideset( $node, $passedArgs, $operatorContent, $name,
									$smth1 = null, $smth2 = null, $smth3 = null ) {
		// Sideset parsing is not completed yet (Waiting for MML)
		$mmlMrow = new MMLmrow( TexClass::OP );
		$mmlMultiscripts = new MMLmmultiscripts( "", [ Tag::ALIGN => "left" ] );
		$in1 = $node->getArg1()->renderMML();
		$in2 = $node->getArg2()->renderMML();
		return $mmlMrow->encapsulate( $mmlMultiscripts->encapsulate( $in2 . "<mprescripts/>" . $in1 ) );
	}

	public static function spacer( $node, $passedArgs, $operatorContent, $name, $withIn = null, $smth2 = null ) {
		// var node = parser.create('node', 'mspace', [], { width: (0, lengths_js_1.em)(space) });
		$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
		$width  = MMLutil::round2em( $withIn );
		$mspace = new MMLmspace( "", [ "width" => $width ] );
		return $mstyle->encapsulate( $mspace->encapsulate( "" ) );
	}

	public static function texAtom( $node, $passedArgs, $operatorContent, $name, $texClass = null ) {
		switch ( $name ) {
			case "mathclose":
				$mrow = new MMLmrow();
				$mrow2 = new MMLmrow( $texClass, [] );
				$inner = $node->getArg()->renderMML();
				return $mrow->encapsulate( $mrow2->encapsulate( $inner ) );
			case "mathbin":
				// no break
			case "mathop":
				// no break
			case "mathrel":
				$mrow2 = new MMLmrow( $texClass, [] );
				$inner = $node->getArg()->renderMML();
				return $mrow2->encapsulate( $inner );
			default:
				$mrow = new MMLmrow( TexClass::ORD );
				$mrow2 = new MMLmrow( $texClass, [] );
				$inner = $node->getArg()->renderMML();
				return $mrow->encapsulate( $mrow2->encapsulate( $inner ) );
		}
	}

	public static function hBox( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		switch ( $name ) {
			case "mbox":
				// These seem special case for mbox, otherwise mbox parsed like hbox
				if ( $operatorContent == "\\textvisiblespace" ) {
					// there is also custom mapping for now to that,
					// for TexUtilMMLTest this seems to only occur here though
					$mmlMrow = new MMLmrow();
					$mtext = new MMLmtext();
					return $mmlMrow->encapsulate( $mtext->encapsulate( "&#x2423;" ) );

				} elseif ( $operatorContent != null ) { // ok ?? \\AA \\Coppa ....
					$mmlMrow = new MMLmrow();
					$mtext = new MMLmtext( "", [ "mathcolor" => "red" ] );
					return $mmlMrow->encapsulate( $mtext->encapsulate( $operatorContent ) );
				}
				// no break
			case "hbox":
				$mmlMrow = new MMLmrow();
				$mstyle = new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ] );
				$mtext = new MMLmtext();
				return $mmlMrow->encapsulate( $mstyle->encapsulate(
					$mtext->encapsulate( $node->getArg() ) ) ); // renderMML for arg ?
			case "text":
				$mmlMrow = new MMLmrow();
				$mtext = new MMLmtext();
				return $mmlMrow->encapsulate( $mtext->encapsulate( $node->getArg() ) ); // renderMML for arg ?
			case "textbf":
				// no break
			case "textit":
				// no break
			case "textrm":
				// no break
			case "textsf":
				// no break
			case "texttt":
				$mmlMrow = new MMLmrow();
				$mtext = new MMLmtext( "", MMLParsingUtil::getFontArgs( $name, null, null ) );
				return $mmlMrow->encapsulate( $mtext->encapsulate( $node->getArg()->renderMML() ) );
		}

		$msubsup = new MMLmsubsup( "tbd HBox" );
		// $node->getArg1()->renderMML() . $node->getArg2()->renderMML()
		return $msubsup->encapsulate( "undefined hbox" );
	}

	public static function setStyle( $node, $passedArgs, $operatorContent, $name,
									 $smth = null, $smth1 = null, $smth2 = null ) {
		// Just discard setstyle since they are captured in TexArray now}
		return " ";
	}

	public static function not( $node, $passedArgs, $operatorContent, $name, $smth = null,
								$smth1 = null, $smth2 = null ) {
		// This is only tested for \not statement without follow-up parameters
		$mmlMrow = new MMLmrow( TexClass::REL );
		if ( $node instanceof Literal ) {
			$mpadded = new MMLmpadded( "", [ "width" => "0" ] );
			$mtext = new MMLmtext();
			return $mmlMrow->encapsulate( $mpadded->encapsulate( $mtext->encapsulate( "&#x29F8;" ) ) );
		} else {
			$mmlMrow->encapsulate( "TBD Not" );
		}
	}

	public static function vbox( $node, $passedArgs, $operatorContent, $name, $smth = null ) {
		// This is only example functionality for vbox("ab").
		// TBD: it should be discussed if vbox is supported since it
		// does not seem to be supported by mathjax
		if ( is_string( $node->getArg() ) ) {
			$mmlMover = new MMLmover();
			$mmlmrow = new MMLmrow();
			$arr1 = str_split( $node->getArg() );
			$inner = "";
			foreach ( $arr1 as $char ) {
				$inner .= $mmlmrow->encapsulate( $char );
			}
			return $mmlMover->encapsulate( $inner );
		}
		$mmr = new MMLmrow();
		return $mmr->encapsulate( "no implemented vbox" );
	}

	public static function sqrt( $node, $passedArgs, $operatorContent, $name ) {
		$mrow = new MMLmrow();

		// There is an additional argument for the root
		if ( $node instanceof Fun2sq ) {
			$mroot = new MMLmroot();

			// In case of an empty curly add an mrow
			$arg2Rendered = $node->getArg2()->renderMML( $passedArgs );
			if ( trim( $arg2Rendered ) === "" ) {
				$arg2Rendered = $mrow->getEmpty();
			}
			return $mrow->encapsulate(
				$mroot->encapsulate(
					$arg2Rendered .
					$mrow->encapsulate(
						$node->getArg1()->renderMML( $passedArgs )
					)
				)
			);
		}
		$msqrt = new MMLmsqrt();
		// Currently this is own implementation from Fun1.php
		return $mrow->encapsulate( // assuming that this is always encapsulated in mrow
			$msqrt->encapsulate(
				$node->getArg()->renderMML( $passedArgs )
			)
		);
	}

	public static function xArrow( $node, $passedArgs, $operatorContent, $name, $chr = null, $l = null, $r = null ) {
		$defWidth = "+" . MMLutil::round2em( ( $l + $r ) / 18 );
		$defLspace = MMLutil::round2em( $l / 18 );

		$mover = new MMLmover();
		$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ] );
		$moArrow = new MMLmo( Texclass::REL, [] );
		$char = IntlChar::chr( $chr );

		$mpaddedArgs = [ "height" => "-.2em", "lspace" => $defLspace, "voffset" => "-.2em", "width" => $defWidth ];
		$mpadded = new MMLmpadded( "", $mpaddedArgs );
		$mspace = new MMLmspace( "", [ "depth" => ".25em" ] );
		if ( $node instanceof Fun2sq ) {
			$mmlMrow = new MMLmrow();
			$mmlUnderOver = new MMLmunderover();
			return $mmlMrow->encapsulate( $mmlUnderOver->encapsulate(
				$mstyle->encapsulate( $moArrow->encapsulate( $char ) ) .
				$mpadded->encapsulate(
					$mmlMrow->encapsulate(
						$node->getArg1()->renderMML()
					) .
					$mspace->encapsulate( "" )
				) .
				$mpadded->encapsulate(
					$node->getArg2()->renderMML()
				)
			) );

		}
		return $mover->encapsulate(
			$mstyle->encapsulate( $moArrow->encapsulate( $char ) ) .
			$mpadded->encapsulate(
				$node->getArg()->renderMML() .
				$mspace->encapsulate( "" )
			)
		);
	}
}
