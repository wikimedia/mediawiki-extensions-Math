<?php
namespace MediaWiki\Extension\Math\WikiTexVC\MMLmappings;

use IntlChar;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmerror;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmfrac;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmmultiscripts;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmphantom;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmroot;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsqrt;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsup;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtable;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtd;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtr;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunder;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunderover;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\FQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2sq;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun4;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Matrix;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\UQ;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

/**
 * Parsing functions for specific recognized mappings.
 * Usually the parsing functions are invoked from the BaseMethods classes.
 */
class BaseParsing {

	public static function accent( $node, $passedArgs, $name, $operatorContent,
								   string $accent, ?bool $stretchy = null ): MMLbase {
		// Currently this is own implementation from Fun1.php
		// TODO The first if-clause is mathjax specific (and not necessary by generic parsers)
		// and will most probably removed (just for running all tc atm)
		if ( $accent == "00B4" || $accent == "0060" ) {
			$attrs = [ Tag::SCRIPTTAG => "true" ];
		} else {
			if ( $stretchy === null ) {
				$attrs = [];
			} else {
				$attrs = [ "stretchy" => $stretchy ? "true" : "false" ];
			}
		}
		if ( trim( $operatorContent ) === '\\vec' ) {
			$attrs['class'] = 'mwe-math-vec';
		}

		// Fetching entity from $accent key tbd
		$entity = MMLutil::createEntity( $accent );
		if ( !$entity ) {
			$entity = $accent;
		}
		$inner = $node->getArg()->toMMLtree( $passedArgs );

		return MMLmover::newSubtree(
			!$inner->isEmpty() ? $inner : ( new MMLmrow() ),
			new MMLmo( '', $attrs, $entity )
		);
	}

	public static function array( $node, $passedArgs, $operatorContent, $name, $begin = null, $open = null,
		$close = null, $align = null, $spacing = null,
		$vspacing = null, $style = null, $raggedHeight = null
	): MMLbase {
		$output = [];
		if ( $open != null ) {
			$resDelimiter = TexUtil::getInstance()->delimiter( trim( $open ) ) ?? false;
			if ( $resDelimiter ) {
				// $retDelim = $bm->checkAndParseDelimiter($open, $node,$passedArgs,true);
				$output[] = ( new MMLmo( TexClass::OPEN, [], $resDelimiter[0] ) );
			}
		}
		if ( $name == "Bmatrix" || $name == "bmatrix" || $name == "Vmatrix"
			|| $name == "vmatrix" || $name == "smallmatrix" || $name == "pmatrix" || $name == "matrix" ) {
			// This is a workaround and might be improved mapping BMatrix to Matrix directly instead of array
			return self::matrix( $node, $passedArgs, $operatorContent, $name,
				$open, $close, null, null, null, null, true );

		} else {
			$output[] = new MMLmrow( TexClass::ORD, [], $node->getMainarg()->toMMLtree() );
		}

		if ( $close != null ) {
			$resDelimiter = TexUtil::getInstance()->delimiter( trim( $close ) ) ?? false;
			if ( $resDelimiter ) {
				$output[] = new MMLmo( TexClass::CLOSE, [], $resDelimiter[0] );
			}
		}
		return new MMLarray( ...$output );
	}

	public static function alignAt( Matrix $node, $passedArgs, $operatorContent, $name, $align = null,
		$smth2 = null
	): MMLbase {
		$mtable  = new MMLmtable( '', [ 'displaystyle' => 'true' ] );
		$inner = [];
		$align ??= $node->getAlign();
		$rowNo = 0;
		$rowCount = count( $node->getArgs() );
		foreach ( $node as $tableRow ) {
			$rowNo++;
			$mtds = [];
			$colNo = 0;
			$isEmptyLine = true;
			$attributes = [];
			$rowSpecs = $tableRow->getRowSpecs();
			if ( $rowSpecs ) {
				$attributes['style'] = "padding-bottom: {$rowSpecs->getCssLength()};";
			}
			foreach ( $tableRow->getArgs() as $tableCell ) {
				$class = '';
				if ( in_array( $align[$colNo] ?? [], [ 'l', 'r' ] ) ) {
					// @phan-suppress-next-line PhanTypeArraySuspiciousNullable
					$class .= ' mwe-math-columnalign-' . $align[$colNo];
				}
				$class = trim( $class );
				$isEmptyLine = $isEmptyLine && $tableCell->isEmpty();
				$mtds[] = new MMLmtd( "",
					$attributes + ( $class ? [ 'class' => $class ] : [] ),
					$tableCell->toMMLtree() );
				$colNo++;
			}
			// empty trailing lines with only one empty cell should not be rendered
			if ( $rowNo === $rowCount && $colNo === 1 && $isEmptyLine ) {
				continue;
			}
			$inner[] = new MMLmtr( "", [], ...$mtds );
		}
		$mtable->addChild( ...$inner );
		return $mtable;
	}

	public static function boldsymbol( $node, $passedArgs, $operatorContent, $name, $smth = null,
		$smth2 = null
	): MMLbase {
		$passedArgs = array_merge( $passedArgs, [ 'mathvariant' => Variants::BOLDITALIC ] );
		return new MMLmrow( TexClass::ORD, [], $node->getArg()->toMMLtree( $passedArgs ) );
	}

	public static function cancel( Fun1 $node, $passedArgs, $operatorContent, $name, $notation = '' ): MMLbase {
		return self::menclose( $node->getArg()->toMMLtree(), $notation );
	}

	private static function menclose( MMLbase $content, string $notation ): MMLbase {
		$classes = [ 'menclose' ];
		foreach ( explode( ' ', $notation ) as $element ) {
			$classes[] = 'menclose-' . $element;
		}

		return new MMLmrow( '', [ 'class' => implode( ' ', $classes ) ],
			new MMLmrow( '', [], $content ) );
	}

	public static function cancelTo( $node, $passedArgs, $operatorContent, $name, string $notation = '' ): MMLbase {
		// Match MathJax's 0.1em upward adjustment for the cancelto annotation:
		// https://github.com/mathjax/MathJax-src/blob/master/ts/input/tex/cancel/CancelConfiguration.ts
		$mpAdded = new MMLmpadded( "", [ "depth" => "-.1em", "voffset" => ".1em",
			"class" => "mwe-math-cancelto" ],
			$node->getArg1()->toMMLtree() );
		$menclose = self::menclose( $node->getArg2()->toMMLtree(), $notation );
		return new MMLmrow( TexClass::ORD, [], MMLmsup::newSubtree( $menclose, $mpAdded ) );
	}

	public static function chemCustom( $node, $passedArgs, $operatorContent, $name, $translation = null ): MMLmerror {
		return MMLmerror::newFromText( $translation ?: 'tbd chemCustom' );
	}

	public static function customLetters( $node, $passedArgs, $operatorContent, $name, $char,
			$isOperator = false
	): MMLbase {
		if ( $isOperator ) {
			return new MMLmrow( TexClass::ORD, [], new MMLmo( "", [], $char ) );
		}
		$variant = $passedArgs['mathvariant'] ?? Variants::NORMAL;
		return new MMLmrow( TexClass::ORD, [], new MMLmi( "", [ 'mathvariant' => $variant ], $char ) );
	}

	public static function cFrac( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		$mstyle1 = new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ],
			new MMLmrow( TexClass::ORD, [], $node->getArg1()->toMMLtree() ) );
		$mstyle2 = new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ],
			new MMLmrow( TexClass::ORD, [], $node->getArg2()->toMMLtree() ) );
		// See TexUtilMMLTest testcase 81
		// (mml3 might be erronous here, but this element seems to be rendered correctly)
		$whatIsThis = new MMLmrow( TexClass::ORD, [],
			new MMLmpadded( "", [ "depth" => "3pt", "height" => "8.6pt", "width" => "0" ] ) );
		$inner = new MMLmrow( TexClass::ORD, [], $whatIsThis, $mstyle2 );
		$mfrac = MMLmfrac::newSubtree(
			new MMLmrow( TexClass::ORD, [], $whatIsThis, $mstyle1 ), $inner );
		return new MMLmrow( TexClass::ORD, [], $mfrac );
	}

	public static function crLaTeX( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		return new MMLmspace( "", [ "linebreak" => "newline" ] );
	}

	public static function dots( $node, $passedArgs, $operatorContent, $name, $smth = null, $smth2 = null ): MMLbase {
		switch ( $operatorContent['dots'] ?? 'dotso' ) {
			case 'dotsb':
				return new MMLmo( "", $passedArgs, "&#x22EF;" );
			case 'dotsi':
				return new MMLarray( ( new Literal( '\\!' ) )->toMMLTree(),
					new MMLmo( "", $passedArgs, "&#x22EF;" ) );
			case 'rightdelim':
				return new MMLarray( new MMLmo( "", $passedArgs, "&#x2026;" ),
					( new Literal( '\\,' ) )->toMMLTree() );
			default:
				return new MMLmo( "", $passedArgs, "&#x2026;" );
		}
	}

	public static function genFrac( $node, $passedArgs, $operatorContent, $name,
		$left = null, $right = null, $thick = null, $style = null
	): MMLbase {
		// Actually this is in AMSMethods, consider refactoring  left, right, thick, style
		$bm = new BaseMethods();
		$ret = $bm->checkAndParseDelimiter( $name, $node, $passedArgs, $operatorContent, true );
		if ( !$ret->isEmpty() ) {
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
		$displayStyle = "false";
		if ( in_array( $thick, [ 'thin', 'medium', 'thick', '0' ], true ) ) {
			$attrs = array_merge( $attrs, [ "linethickness" => $thick ] );
		}
		if ( $style !== '' ) {
			$styleDigit = intval( $style, 10 );
			$styleAlpha = [ 'D', 'T', 'S', 'SS' ][$styleDigit];
			if ( $styleAlpha == null ) {
				return new MMLmrow( TexClass::ORD, [], new MMLmtext( "", [], "Bad math style" ) );
			}

			if ( $styleAlpha === 'D' ) {
				// MathJax fences \genfrac with \bigg in display style and \big otherwise
				// swh:1:cnt:191254e9b9aedba501b4f734fb8f0b6cd9217050;lines=377-378
				$displayStyle = "true";
				$styleAttr = [ "minsize" => TexUtil::getInstance()->callback( '\\bigg' )[2] ];

			} else {
				$styleAttr = [ "minsize" => TexUtil::getInstance()->callback( '\\big' )[2] ];
			}
		} else {
			// Inherit delimiter size and script level when no explicit style was requested.
			$styleAttr = [];
		}
		$output = [];
		if ( $left ) {
			$mrowOpen = new MMLmrow( TexClass::OPEN, [], new MMLmo( "", $styleAttr, $left ) );
			$output[] = $mrowOpen;
		}
		$mrow1 = new MMLmrow( TexClass::ORD, [], $node->getArg1()->toMMLtree() );
		$mrow2 = new MMLmrow( TexClass::ORD, [], $node->getArg2()->toMMLtree() );

		$output[] = MMLmfrac::newSubtree( $mrow1, $mrow2, "", $attrs );
		if ( $right ) {
			$mrowClose = new MMLmrow( TexClass::CLOSE, [], new MMLmo( "", $styleAttr, $right ) );
			$output[] = $mrowClose;
		}
		$output = new MMLmrow( TexClass::ORD, [], ...$output );
		if ( $style !== '' ) {
			$output = new MMLmstyle( "", [ "displaystyle" => $displayStyle, "scriptlevel" => "0" ], $output );
		}

		return new MMLmrow( TexClass::ORD, [], $output );
	}

	public static function frac( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		if ( $node instanceof Fun2 ) {
			$inner = [ new MMLmrow( TexClass::ORD, [], $node->getArg1()->toMMLtree() ),
				new MMLmrow( TexClass::ORD, [], $node->getArg2()->toMMLtree() ) ];
		} elseif ( $node instanceof DQ ) {
			$inner = [ new MMLmrow( TexClass::ORD, [], $node->getBase()->toMMLtree() ),
				new MMLmrow( TexClass::ORD, [], $node->getDown()->toMMLtree() ) ];
		} else {
			$inner = [];
			foreach ( $node->getArgs() as $arg ) {
				$rendered = is_string( $arg ) ? $arg : $arg->toMMLtree();
				$inner[] = new MMLmrow( TexClass::ORD, [], $rendered );
			}
		}
		$mfrac = MMLmfrac::newSubtree( $inner[0], $inner[1] );
		return new MMLmrow( TexClass::ORD, [], $mfrac );
	}

	public static function hline( $node, $passedArgs, $operatorContent, $name,
			$smth1 = null, $smth2 = null, $smth3 = null, $smth4 = null
	): MMLbase {
		// HLine is most probably not parsed this way, since only parsed in Matrix context
		return new MMLmrow( "tbd", [], new MMLmtext( "", [], "HLINE TBD" ) );
	}

	public static function hskip( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		if ( $node->getArg()->isCurly() ) {
			$unit = MMLutil::squashLitsToUnit( $node->getArg() );
			if ( !$unit ) {
				return new MMLarray();
			}
			$em = MMLutil::dimen2em( $unit );
		} else {
			// Prevent parsing in unmapped cases
			return new MMLarray();
		}
		// Added kern j4t
		if ( $name == "mskip" || $name == "mkern" || "kern" ) {
			$args = [ "width" => $em ];
		} else {
			return new MMLarray();
		}

		return new MMLmspace( "", $args );
	}

	public static function handleOperatorName( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		// In example "\\operatorname{a}"
		$applyFct = self::getApplyFct( $operatorContent );
		$mmlNot = new MMLarray();
		if ( isset( $operatorContent['not'] ) && $operatorContent['not'] ) {
			$mmlNot = MMLParsingUtil::createNot();
		}
		$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::OP, 'mathvariant' => Variants::NORMAL ] );
		$state = [ 'squashLiterals' => true ];
		$inner = $node->getArg()->toMMLtree( $passedArgs, $state );
		if ( $inner instanceof MMLarray && count( $inner->getChildren() ) == 1 ) {
			$mi = $inner->getChildren()[0];
			// this check needs to be made explicit for phan
			if ( $mi instanceof MMLmi ) {
				$inner = $mi;
			}
		}
		return new MMLarray( $mmlNot, $inner, $applyFct );
	}

	public static function matrix( Matrix $node, $passedArgs, $operatorContent,
		$name, $open = null, $close = null, $align = null, $spacing = null,
		$vspacing = null, $style = null, $cases = null, $numbered = null
	): MMLbase {
		$resInner = [];
		$boarder = $node->getBoarder();
		if ( !$align ) {
			$align = $node->getAlign();
		}
		$rowNo = 0;
		$lines = $node->getLines();
		foreach ( $node as $row ) {
			$innerInnter = [];
			$colNo = 0;
			$isEmptyLine = true;
			$rowAttributes = [];
			$rowSpecs = $row->getRowSpecs();
			if ( $rowSpecs ) {
				$rowAttributes['style'] = "padding-bottom: {$rowSpecs->getCssLength()};";
			}
			foreach ( $row  as $cell ) {
				$usedArg = clone $cell;
				if ( $usedArg instanceof TexArray &&
					$usedArg->getLength() >= 1
				) {
					$firstArg = $usedArg[0];
					if ( $firstArg instanceof Literal &&
						$firstArg->getArg() === '\\hline '
					) {
						$usedArg->pop();
					}
				}
				$mtdAttributes = $rowAttributes;
				$texclass = $lines[$rowNo] ? TexClass::TOP : '';
				$texclass .= $lines[$rowNo + 1] ?? false ? ' ' . TexClass::BOTTOM : '';
				$texclass .= $boarder[$colNo] ?? false ? ' ' . TexClass::LEFT : '';
				$texclass .= $boarder[$colNo + 1 ] ?? false ? ' ' . TexClass::RIGHT : '';
				if ( in_array( $align[$colNo] ?? [], [ 'l', 'r' ] ) ) {
					// @phan-suppress-next-line PhanTypeArraySuspiciousNullable
					$texclass .= ' mwe-math-columnalign-' . $align[$colNo];
				}
				$texclass = trim( $texclass );
				if ( $texclass ) {
					$mtdAttributes['class'] = $texclass;
				}
				$state = [ 'inMatrix'	=> true ];
				$isEmptyLine = $isEmptyLine && $usedArg->isEmpty();
				$innerInnter[] = new MMLmtd( "", $mtdAttributes, $usedArg->toMMLtree( $passedArgs, $state ) );
				$colNo++;
			}
			$rowNo++;
			// empty trailing lines with only one empty cell should not be rendered
			if ( $rowNo === count( $lines ) && $colNo === 1 && $isEmptyLine ) {
				break;
			}
			$resInner[] = new MMLmtr( "", [], ...$innerInnter );
		}
		$mtable = new MMLmtable( '',
		$name === 'smallmatrix' ?
		[ 'class' => 'mwe-math-smallmatrix' ] : []
		);
		if ( $cases || ( $open != null && $close != null ) ) {
			$bm = new BaseMethods();
			$mmlMoOpen = $bm->checkAndParseDelimiter( $open, $node, [], [],
				true, TexClass::OPEN );
			if ( $mmlMoOpen->isEmpty() ) {
				$mmlMoOpen = new MMLmo( TexClass::OPEN, [], $open ?? '' );
			}

			// MathJax gives an empty closing fence the width of a null delimiter.
			$closeAtts = [ "data-mwe-fence" => "true", "stretchy" => "true", "symmetric" => "true" ];
			$mmlMoClose = $bm->checkAndParseDelimiter( $close, $node, $closeAtts,
				null, true, TexClass::CLOSE );
			if ( $mmlMoClose->isEmpty() ) {
				$mmlMoClose = ( new MMLmo( TexClass::CLOSE, $closeAtts, $close ?? '' ) );
			}
			$mtable->addChild( ...$resInner );
			return new MMLmrow( TexClass::ORD, [], $mmlMoOpen, $mtable, $mmlMoClose );
		}
		$mtable->addChild( ...$resInner );
		return $mtable;
	}

	public static function over( $node, $passedArgs, $operatorContent, $name, $id = null ): MMLbase {
		$attributes = [];
		$start = new MMLarray();
		$tail = new MMLarray();
		if ( trim( $name ) === "\\atop" ) {
			$attributes = [ "linethickness" => "0" ];
		} elseif ( trim( $name ) == "\\choose" ) {
			// Let the operator dictionary stretch the parentheses to the contextual fraction height.
			$start = new MMLmrow( TexClass::OPEN, [],
				new MMLmo( "", [], "(" )
			);
			$tail = new MMLmrow( TexClass::CLOSE, [],
				new MMLmo( "", [], ")" )
			);
			$attributes = [ "linethickness" => "0" ];
		}
		if ( $node instanceof Fun2 ) {
			$mfrac = MMLmfrac::newSubtree( new MMLmrow( "", [], $node->getArg1()->toMMLtree() ),
				new MMLmrow( "", [], $node->getArg2()->toMMLtree() ), "", $attributes );
			if ( $start->isEmpty() ) {
				return $mfrac;
			}
			return new MMLmrow( TexClass::ORD, [], $start, $mfrac, $tail );
		}
		$inner = [];
		foreach ( $node->getArgs() as $arg ) {
			if ( is_string( $arg ) && str_contains( $arg, $name ) ) {
				continue;
			}
			$rendered = $arg instanceof TexNode ? $arg->toMMLtree() : $arg;
			$inner[] = new MMLmrow( "", [], $rendered );
		}
		$mfrac = MMLmfrac::newSubtree( $inner[0], $inner[1], "", $attributes );
		if ( $start->isEmpty() ) {
			return $mfrac;
		}
		return new MMLmrow( TexClass::ORD, [], $start, $mfrac, $tail );
	}

	public static function oint( $node, $passedArgs, $operatorContent,
		$name, $uc = null, $attributes = null, $smth2 = null
	): MMLbase {
		// This is a custom mapping not in js.
		switch ( trim( $name ) ) {
			case "\\oint":
			case "\\P":
				return new MMLmo( "", [], MMLutil::uc2xNotation( $uc ) );
			case "\\oiint":
			case "\\oiiint":
			case "\\ointctrclockwise":
			case "\\varointclockwise":
				// FIXME: the trailing thin space has no known source
				return new MMLmrow( TexClass::ORD, [],
					new MMLmstyle( "", [ "mathsize" => "2.07em" ],
						new MMLmtext( "", $attributes, MMLutil::uc2xNotation( $uc ) ),
						new MMLmspace( "", [ "width" => MMLutil::round2em( 3 / 18 ) ] )
					)
				);
			default:
				return MMLmerror::newFromText( "not found in OintMethod" );
		}
	}

	public static function overset( $node, $passedArgs, $operatorContent, $name, $id = null ): MMLbase {
		if ( $node instanceof DQ ) {
			return new MMLmrow( TexClass::ORD, [],
				MMLmover::newSubtree( new MMLmrow(
					"",
					[],
					$node->getDown()->toMMLtree() ),
					$node->getDown()->toMMLtree()
				)
			);
		}
		return new MMLmrow( TexClass::ORD, [],
			MMLmover::newSubtree(
				new MMLmrow(
					"",
					[],
				$node->getArg2()->toMMLtree() ),
				$node->getArg1()->toMMLtree()
			)
		);
	}

	public static function phantom( $node, $passedArgs, $operatorContent,
		$name, $vertical = null, $horizontal = null, $smh3 = null
	): MMLbase {
		$attrs = [];
		if ( $vertical ) {
			$attrs = array_merge( $attrs, [ "width" => "0" ] );
		}
		if ( $horizontal ) {
			$attrs = array_merge( $attrs, [ "depth" => "0", "height" => "0" ] );
		}
		return new MMLmrow(
			TexClass::ORD,
			[],
			new MMLmrow(
				TexClass::ORD,
				[],
				new MMLmpadded(
					"",
					$attrs,
					new MMLmphantom( "", [], $node->getArg()->toMMLtree() )
				)
			)
		);
	}

	public static function raiseLower( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		if ( !$node instanceof Fun2 ) {
			return new MMLarray();
		}

		$arg1 = $node->getArg1();
		// the second check is to avoid a false positive for PhanTypeMismatchArgumentSuperType
		if ( $arg1->isCurly() && $arg1 instanceof TexArray ) {
			$unit = MMLutil::squashLitsToUnit( $arg1 );
			if ( !$unit ) {
				return new MMLarray();
			}
			$em = MMLutil::dimen2em( $unit );
			if ( !$em ) {
				return new MMLarray();
			}
		} else {
			return new MMLarray();
		}

		if ( trim( $name ) === "\\raise" ) {
			$args = [ "height" => MMLutil::addPreOperator( $em, "+" ),
				"depth" => MMLutil::addPreOperator( $em, "-" ),
				"voffset" => MMLutil::addPreOperator( $em, "+" ) ];
		} elseif ( trim( $name ) === "\\lower" ) {
			$args = [ "height" => MMLutil::addPreOperator( $em, "-" ),
				"depth" => MMLutil::addPreOperator( $em, "+" ),
				"voffset" => MMLutil::addPreOperator( $em, "-" ) ];
		} else {
			// incorrect name, should not happen, prevent erroneous mappings from getting rendered.
			return new MMLarray();
		}
		return new MMLmrow( "", [], new MMLmpadded( "", $args, $node->getArg2()->toMMLtree() ) );
	}

	public static function underset( $node, $passedArgs, $operatorContent, $name, $smh = null ): MMLbase {
		$inrow = $node->getArg2()->toMMLtree();
		$arg1 = $node->getArg1()->toMMLtree();
		if ( !$inrow->isEmpty() && !$arg1->isEmpty() ) {
			return new MMLmrow( TexClass::ORD, [], MMLmunder::newSubtree( $inrow, $arg1 ) );
		}

		// If there are no two elements in munder, not render munder
		return new MMLmrow( TexClass::ORD, [], $inrow, $arg1 );
	}

	public static function underOver( Fun1 $node, $passedArgs, $operatorContent,
		$name, $operatorSymbol = null, $stack = null ): MMLbase {
		// tbd verify if stack interpreted correctly ?
		$texClass = $stack ? TexClass::OP : TexClass::ORD; // ORD or ""

		$fname = $node->getFname();
		if ( str_starts_with( $fname, '\\over' ) ) {
			$movun = new MMLmover();
		} elseif ( str_starts_with( $fname, '\\under' ) ) {
			$movun = new MMLmunder();
		} else {
			// incorrect name, should not happen, prevent erroneous mappings from getting rendered.
			return MMLmerror::newFromText(
				'underOver rendering requires macro to start with either \\under or \\over.' );
		}

		$mo = new MMLmo( "", [], $operatorSymbol );
		return new MMLmrow( $texClass, [], $movun::newSubtree( $node->getArg()->toMMLtree( $passedArgs ), $mo ) );
	}

	public static function mathFont( $node, $passedArgs, $operatorContent, $name, $mathvariant = null ): MMLbase {
		$args = MMLParsingUtil::getFontArgs( $name, $mathvariant, $passedArgs );
		$state = [];

			return new MMLmrow( TexClass::ORD, [], $node->getArg()->toMMLtree( $args, $state ) );
	}

	public static function mathChoice( $node, $passedArgs, $operatorContent, $name, $smth = null ): MMLbase {
		if ( !$node instanceof Fun4 ) {
			return MMLmerror::newFromText( "Wrong node type in mathChoice" );
		}

		/**
		 * Parametrization for mathchoice:
		 * \mathchoice
		 * {<material for display style>}
		 * {<material for text style>}
		 * {<material for script style>}
		 * {<material for scriptscript style>}
		 */

		if ( isset( $operatorContent["styleargs"] ) ) {
			$styleArgs = $operatorContent["styleargs"];
			$displayStyle = $styleArgs["displaystyle"] ?? "true";
			$scriptLevel = $styleArgs["scriptlevel"] ?? "0";

			if ( $displayStyle == "true" && $scriptLevel == "0" ) {
				// This is displaystyle
				return $node->getArg1()->toMMLtree( $passedArgs, $operatorContent );
			} elseif ( $displayStyle == "false" && $scriptLevel == "0" ) {
				// This is textstyle
				return $node->getArg2()->toMMLtree( $passedArgs, $operatorContent );
			} elseif ( $displayStyle == "false" && $scriptLevel == "1" ) {
				// This is scriptstyle
				return $node->getArg3()->toMMLtree( $passedArgs, $operatorContent );
			} elseif ( $displayStyle == "false" && $scriptLevel == "2" ) {
				// This is scriptscriptstyle
				return $node->getArg4()->toMMLtree( $passedArgs, $operatorContent );
			}
		}
		// By default render displaystyle
		return $node->getArg1()->toMMLtree( $passedArgs, $operatorContent );
	}

	public static function makeBig( $node, $passedArgs, $operatorContent, $name, $texClass = null,
		$size = null
	): MMLbase {
		// $size is the kernel height of \big..\Bigg (8.5pt..17.5pt) scaled by MathJax's 1.2/.85
		// swh:1:cnt:72f94735bba07ecbc4214f3cc409de0e137ff1ee;lines=1534-1541
		// swh:1:cnt:4fcf2badb30fa89a86be332c1481b0daaf21d367;lines=47
		$passedArgs = array_merge( $passedArgs, [ "maxsize" => $size, "minsize" => $size ] );
		// Sieve arg if it is a delimiter (it seems args are not applied here
		$bm = new BaseMethods();
		$argcurrent = trim( $node->getArg() );
		switch ( $argcurrent ) {
			case "\\|":
			case "\\vert":
			case "|":
			case "\\uparrow":
			case "\\downarrow":
			case "\\Uparrow":
			case "\\Downarrow":
			case "\\updownarrow":
			case "/":
			case "\\backslash":
			case "\\Updownarrow":
				$passedArgs = array_merge( $passedArgs, [ "stretchy" => "true", "symmetric" => "true" ] );
				break;
		}

		if ( in_array( $name, [ "\\bigl", "\\Bigl", "\\biggl", "\\Biggl" ] ) ) {
			$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::OPEN ] );
		}

		if ( in_array( $name, [ "\\bigr", "\\Bigr", "\\biggr", "\\Biggr" ] ) ) {
			$passedArgs = array_merge( $passedArgs, [ Tag::CLASSTAG => TexClass::CLOSE ] );
		}

		$ret = $bm->checkAndParseDelimiter( $node->getArg(), $node, $passedArgs, $operatorContent, true );
		if ( !$ret->isEmpty() ) {
			return $ret;
		}

		$argPrep = $node->getArg();
		return new MMLmrow( TexClass::ORD, [],
			new MMLmrow( $texClass, [], new MMLmo( "", $passedArgs, $argPrep ) )
		);
	}

	public static function machine( $node, $passedArgs, $operatorContent, $name, $type = null ): MMLbase {
		// this could also be shifted to MhChem.php renderMML for ce
		// For parsing chem (ce) or ??? (pu)
		return new MMLmrow( "", [], $node->getArg()->toMMLtree() );
	}

	public static function setFont( $node, $passedArgs, $operatorContent, $name, $variant = null ): MMLbase {
		return self::mathFont( $node, $passedArgs, $operatorContent, $name, $variant );
	}

	public static function sideset( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		if ( !array_key_exists( "sideset", $operatorContent ) ) {
			return MMLmerror::newFromText( "Error parsing sideset expression, no succeeding operator found" );
		}

		$op = $operatorContent["sideset"];
		$state = [ 'sideset' => true ];
		$in1 = $node->getArg1()->toMMLtree( [], $state );
		$in2 = $node->getArg2()->toMMLtree( [], $state );

		if ( $op instanceof FQ || $op instanceof DQ || $op instanceof UQ ) {
			$bm = new BaseMethods();
			if ( count( $op->getBase()->getArgs() ) == 1 ) {
				$baseOperator = $op->getBase()->getArgs()[0];
				if ( is_string( $baseOperator ) ) {
					$opParsed = $bm->checkAndParseOperator( $baseOperator,
						null, [ "largeop" => "true", "movablelimits" => "false", "symmetric" => "true" ], [], null );
				} else {
					$opParsed = $baseOperator->toMMLTree();
				}
				if ( $opParsed->isEmpty() ) {
					$opParsed = $op->getBase()->toMMLtree();
				}
			} else {
				$opParsed = MMLmerror::newFromText( "Sideset operator parsing not implemented yet" );
			}
			$down = $op instanceof UQ ? new MMLmrow( "", [] ) : $op->getDown()->toMMLtree();
			$up = $op instanceof DQ ? new MMLmrow( "", [] ) : $op->getUp()->toMMLtree();
			return new MMLmrow(
				TexClass::OP,
				[],
				MMLmunderover::newSubtree(
					new MMLmstyle( "", [ "displaystyle" => "true" ],
					MMLmmultiscripts::newSubtree( $opParsed, $in2, new MMLarray(), $in1 ) ),
					new MMLmrow( "", [], $down ),
					new MMLmrow( "", [], $up )
				)
			);
		}

		if ( $op instanceof Literal ) {
			$bm = new BaseMethods();
			$opParsed = $bm->checkAndParseOperator( $op->getArg(), null, [], [], null );
			if ( $opParsed->isEmpty() ) {
				$opParsed = $op->toMMLtree();
			}
		} else {
			$opParsed = $op->toMMLtree();
		}
		return new MMLmrow( TexClass::OP, [],
			MMLmmultiscripts::newSubtree( $opParsed, $in2, new MMLarray(), $in1, new MMLarray(),
				"", [ Tag::ALIGN => "left" ]
			)
		);
	}

	public static function spacer( $node, $passedArgs, $operatorContent, $name, $withIn = null, $smth2 = null
	): MMLbase {
		return new MMLmspace( "", [ "width" => MMLutil::round2em( $withIn ) ] );
	}

	public static function smash( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		$mpArgs = [];
		$inner = new MMLarray();
		if ( $node instanceof Fun2sq ) {
			$arg1 = $node->getArg1();
			$arg1i = "";
			if ( $arg1->isCurly() ) {
				$arg1i = $arg1->render();
			}

			if ( str_contains( $arg1i, "{b}" ) ) {
				$mpArgs = [ "depth" => "0" ];
			}
			if ( str_contains( $arg1i, "{t}" ) ) {
				$mpArgs = [ "height" => "0" ];
			}
			if ( str_contains( $arg1i, "{tb}" ) || str_contains( $arg1i, "{bt}" ) ) {
				$mpArgs = [ "height" => "0", "depth" => "0" ];
			}

			$inner = $node->getArg2()->toMMLtree();
		} elseif ( $node instanceof Fun1 ) {
			// Implicitly assume "tb" as default mode
			$mpArgs = [ "height" => "0", "depth" => "0" ];
			$inner = $node->getArg()->toMMLtree();
		}
		return new MMLmrow( TexClass::ORD, [], new MMLmpadded( "", $mpArgs, $inner ) );
	}

	public static function texAtom( $node, $passedArgs, $operatorContent, $name, $texClass = null ): MMLbase {
		switch ( $name ) {
			case '\mathbin':
				// no break
			case '\mathop':
				// no break
			case '\mathrel':
				$inner = $node->getArg()->toMMLtree();
				return new MMLmrow( $texClass, [], $inner );
			default:
				$inner = $node->getArg()->toMMLtree();
				return new MMLmrow( TexClass::ORD, [], new MMLmrow( $texClass, [], $inner ) );
		}
	}

	public static function intent( $node, $passedArgs, $operatorContent, $name, $smth = null ): MMLbase {
		if ( !$node instanceof Fun2 ) {
			return new MMLarray();
		}
		// if there is intent annotation add intent to root element
		// match args in row of subargs, unless an element has explicit annotations
		// nested annotations ?
		$arg1 = $node->getArg1();
		$arg2 = $node->getArg2();
		if ( !$arg2->isCurly() ) {
			return new MMLarray();
		}
		// tbd refactor intent form and fiddle in mml or tree
		$intentStr = MMLutil::squashLitsToUnitIntent( $arg2 );
		$intentContent = MMLParsingUtil::getIntentContent( $intentStr );
		$intentParams = MMLParsingUtil::getIntentParams( $intentContent );
		// Sometimes the intent has additioargs = {array[3]} nal args in the same string
		$intentArg = MMLParsingUtil::getIntentArgs( $intentStr );
		if ( !$intentContent && !$intentParams && $intentArg !== null ) {
			// explicit args annotation parsing in literal
			// return $arg1->renderMML([],["intent-params-expl"=>$intentArg]);
			// alternative just add the arg here
			return $arg1->toMMLtree( [ "arg" => $intentArg ] );
		}
		$intentContentAtr = [ "intent" => $intentContent ];
		if ( $intentArg !== null ) {
			$intentContentAtr["arg"] = $intentArg;
		}
		// tbd refine intent params and operator content merging (does it overwrite ??)
		$intentParamsState = $intentParams ? [ "intent-params" => $intentParams ] : $operatorContent;
		// Here are some edge cases, they might go into renderMML in the related element
		if ( str_contains( $intentContent ?? '', "matrix" ) ||
			( $arg1->isCurly() && $arg1->getArgs()[0] instanceof Matrix ) ) {
			$element = $arg1->getArgs()[0];
			$rendered = $element->toMMLtree( [], $intentParamsState );
			return MMLParsingUtil::forgeIntentToSpecificElement( $rendered,
				$intentContentAtr, "mtable" );
		} elseif ( $arg1->isCurly() && count( $arg1->getArgs() ) >= 2 ) {
			// Create a surrounding element which holds the intents
			return new MMLmrow( "", $intentContentAtr, $arg1->toMMLtree( [], $intentParamsState ) );
		} elseif ( $arg1->isCurly() && count( $arg1->getArgs() ) >= 1 ) {
			// Forge the intent attribute to the top-level element after MML rendering
			$element = $arg1->getArgs()[0];
			$rendered = $element->toMMLtree( [], $intentParamsState );
			return MMLParsingUtil::forgeIntentToTopElement( $rendered, $intentContentAtr );
		} else {
			// This is the default case
			return $arg1->toMMLtree( $intentContentAtr, $intentParamsState );
		}
	}

	public static function hBox( $node, $passedArgs, $operatorContent, $name, $smth = null ): MMLbase {
		switch ( trim( $name ) ) {
			case "\\mbox":
				if ( isset( $operatorContent['foundOC'] ) ) {
					$op = $operatorContent['foundOC'];
					$macro = TexUtil::getInstance()->nullary_macro_in_mbox( $op ) ?
						/* tested in \MediaWiki\Extension\Math\Tests\WikiTexVC\TexUtilTest::testUnicodeDefined */
						[ TexUtil::getInstance()->unicode_char( $op ) ] :
						TexUtil::getInstance()->identifier( $op );
					$input = $macro[0] ?? $op;
					return new MMLmrow( TexClass::ORD, [], new MMLmo( "", [], MMLutil::uc2xNotation( $input ) ) );
				} else {
					return new MMLmrow( TexClass::ORD, [], new MMLmtext( "", [], "\mbox" ) );
				}
			case "\\hbox":
				$inner = $node->getArg() instanceof TexNode ? $node->getArg()->toMMLtree() : $node->getArg();
				return new MMLmrow( TexClass::ORD, [],
					new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ],
						new MMLmtext( "", [], $inner )
					)
				);
			case "\\text":
				$inner = $node->getArg() instanceof TexNode ? $node->getArg()->toMMLtree() : $node->getArg();
				return new MMLmrow( TexClass::ORD, [], new MMLmtext( "", [], $inner ) );
			case "\\textbf":
				// no break
			case "\\textit":
				// no break
			case "\\textrm":
				// no break
			case "\\textsf":
				// no break
			case "\\texttt":
				$state = [ "inHBox" => true, 'squashLiterals' => true ];
				$fontArgs = MMLParsingUtil::getFontArgs( $name, null, null );
				$inner = $node->getArg()->isCurly() ? $node->getArg()->toMMLtree(
					$fontArgs, $state )
					: $node->getArg()->toMMLtree( $fontArgs );
				if ( $inner instanceof MMLbase ) {
					$inner = $inner->getTextContent();
				}
				return new MMLmtext( "", $fontArgs, $inner ?? '' );

		}

		return MMLmerror::newFromText( "undefined hbox" );
	}

	public static function setStyle( $node, $passedArgs, $operatorContent, $name,
		$smth = null, $smth1 = null, $smth2 = null
	): MMLmrow {
		// Just discard setstyle since they are captured in TexArray now}
		return new MMLmrow();
	}

	public static function not( $node, $passedArgs, $operatorContent, $name, $smth = null,
		$smth1 = null, $smth2 = null
	): MMLbase {
		// This is only tested for \not statement without follow-up parameters
		if ( $node instanceof Literal ) {
			return MMLParsingUtil::createNot();
		}
		return MMLmerror::newFromText( "TBD implement not" );
	}

	public static function vbox( $node, $passedArgs, $operatorContent, $name, $smth = null ): MMLbase {
		// This is only example functionality for vbox("ab").
		// TBD: it should be discussed if vbox is supported since it
		// does not seem to be supported by mathjax
		if ( is_string( $node->getArg() ) ) {
			$arr1 = str_split( $node->getArg() );
			$inner = [];
			foreach ( $arr1 as $char ) {
				$inner[] = new MMLmrow( TexClass::ORD, [], new MMLmtext( "", [], $char ) );
			}
			return MMLmover::newSubtree( $inner[0], $inner[1] );
		}
		return MMLmerror::newFromText( "no implemented vbox" );
	}

	public static function sqrt( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		// There is an additional argument for the root
		if ( $node instanceof Fun2sq ) {
			// In case of an empty curly add an mrow
			$arg2Rendered = $node->getArg2()->toMMLtree( $passedArgs );
			if ( $arg2Rendered->isEmpty() ) {
				$arg2Rendered = new MMLmrow( TexClass::ORD, [] );
			}
			return new MMLmrow( TexClass::ORD, [],
				MMLmroot::newSubtree(
					$arg2Rendered,
					new MMLmrow( TexClass::ORD, [],
						$node->getArg1()->toMMLtree( $passedArgs )
					)
				)
			);
		}
		// Currently this is own implementation from Fun1.php
		return new MMLmrow( TexClass::ORD, [], // assuming that this is always encapsulated in mrow
			new MMLmsqrt( "", [],
				$node->getArg()->toMMLtree( $passedArgs )
			)
		);
	}

	public static function tilde( $node, $passedArgs, $operatorContent, $name ): MMLbase {
		return new MMLmspace( "", [ "width" => "0.5em" ] );
	}

	public static function xArrow( $node, $passedArgs, $operatorContent, $name, $chr = null, $l = null,
								   $r = null ): MMLbase {
		$defWidth = "+" . MMLutil::round2em( ( $l + $r ) / 18 );
		$defLspace = MMLutil::round2em( $l / 18 );

		$char = IntlChar::chr( $chr );

		// Core has no relative width; Chrome reads "+0.833em" as 0.833em.
		$mpaddedArgs = [ "height" => "-.2em", "lspace" => $defLspace, "voffset" => "-.2em",
			"data-mwe-width" => $defWidth ];
		$mspace = new MMLmspace( "", [ "depth" => ".25em" ] );
		if ( $node instanceof Fun2sq ) {
			return new MMLmrow( TexClass::ORD, [], MMLmunderover::newSubtree(
				new MMLmstyle( "", [ "scriptlevel" => "0" ],
					new MMLmo( Texclass::REL, [], $char )
				),
				new MMLmpadded( "", $mpaddedArgs,
					new MMLmrow( TexClass::ORD, [],
						$node->getArg1()->toMMLtree()
					),
					$mspace
				),
				new MMLmpadded( "", $mpaddedArgs,
					$node->getArg2()->toMMLtree()
				)
			) );

		}
		return MMLmover::newSubtree(
			new MMLmstyle( "", [ "scriptlevel" => "0" ], new MMLmo( Texclass::REL, [], $char ) ),
			new MMLmpadded( "", $mpaddedArgs, $node->getArg()->toMMLtree(), $mspace )
		);
	}

	public static function getApplyFct( array $operatorContent ): MMLbase {
		$applyFct = new MMLarray();
		if ( array_key_exists( "foundNamedFct", $operatorContent ) ) {
			$hasNamedFct = $operatorContent['foundNamedFct'][0];
			$hasValidParameters = $operatorContent["foundNamedFct"][1];
			if ( $hasNamedFct && $hasValidParameters ) {
				$applyFct = MMLParsingUtil::renderApplyFunction();
			}
		}
		return $applyFct;
	}
}
