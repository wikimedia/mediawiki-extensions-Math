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
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunder;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunderover;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\DQ;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun1;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Fun2sq;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\Literal;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexArray;
use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

/**
 * Parsing functions for specific recognized mappings.
 * TexNode::parseToMML() invokes these parsing functions.
 *
 * A callback gets the node, the attributes and the content in braces a literal passes ($passedArgs and
 * $operatorContent, empty for other nodes), the name of the macro, the further entries of its callback in
 * texutil.json, and the context of the node last, by name.
 */
class BaseParsing {

	public static function cancel( Fun1 $node, $passedArgs, $operatorContent, $name,
		$notation = '', RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Cancel: swh:1:cnt:c73232c9e4936ca89f60aa73cec17ac75cd3ed1f;lines=42-48
		return self::menclose( $node->renderArg( $ctx ), $notation );
	}

	private static function menclose( MMLbase $content, string $notation ): MMLbase {
		$classes = [ 'menclose' ];
		foreach ( explode( ' ', $notation ) as $element ) {
			$classes[] = 'menclose-' . $element;
		}

		return new MMLmrow( '', [ 'class' => implode( ' ', $classes ) ],
			new MMLmrow( '', [], $content ) );
	}

	public static function cancelTo( $node, $passedArgs, $operatorContent, $name,
		string $notation = '', RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax CancelTo: swh:1:cnt:c73232c9e4936ca89f60aa73cec17ac75cd3ed1f;lines=57-78
		// Match MathJax's 0.1em upward adjustment for the cancelto annotation:
		// swh:1:cnt:bd892c998627f356523b57097176e2de05ff7509;lines=68-70
		$mpAdded = new MMLmpadded( "", [ "depth" => "-.1em", "voffset" => ".1em",
			"class" => "mwe-math-cancelto" ],
			$node->renderArg1( $ctx ) );
		$menclose = self::menclose( $node->renderArg2( $ctx ), $notation );
		return new MMLmrow( TexClass::ORD, [], MMLmsup::newSubtree( $menclose, $mpAdded ) );
	}

	public static function chemCustom( $node, $passedArgs, $operatorContent, $name,
		$translation = null, RenderContext $ctx = new RenderContext()
	): MMLmerror {
		return MMLmerror::newFromText( $translation ?: 'tbd chemCustom' );
	}

	public static function customLetters( $node, $passedArgs, $operatorContent, $name,
		$char, $isOperator = false, RenderContext $ctx = new RenderContext()
	): MMLbase {
		if ( $isOperator ) {
			return new MMLmrow( TexClass::ORD, [], new MMLmo( "", [], $char ) );
		}
		$variant = $passedArgs['mathvariant'] ?? Variants::NORMAL;
		return new MMLmrow( TexClass::ORD, [], new MMLmi( "", [ 'mathvariant' => $variant ], $char ) );
	}

	public static function cFrac( $node, $passedArgs, $operatorContent, $name,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax CFrac: swh:1:cnt:d1ea310dac6fcde3604a6155f33b8935b1e17349;lines=646-681
		$mstyle1 = new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ],
			new MMLmrow( TexClass::ORD, [], $node->renderArg1( $ctx ) ) );
		$mstyle2 = new MMLmstyle( "", [ "displaystyle" => "false", "scriptlevel" => "0" ],
			new MMLmrow( TexClass::ORD, [], $node->renderArg2( $ctx ) ) );
		// See TexUtilMMLTest testcase 81
		// (mml3 might be erronous here, but this element seems to be rendered correctly)
		$whatIsThis = new MMLmrow( TexClass::ORD, [],
			new MMLmpadded( "", [ "depth" => "3pt", "height" => "8.6pt", "width" => "0" ] ) );
		$inner = new MMLmrow( TexClass::ORD, [], $whatIsThis, $mstyle2 );
		$mfrac = MMLmfrac::newSubtree(
			new MMLmrow( TexClass::ORD, [], $whatIsThis, $mstyle1 ), $inner );
		return new MMLmrow( TexClass::ORD, [], $mfrac );
	}

	public static function crLaTeX( $node, $passedArgs, $operatorContent, $name,
		$smth = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax CrLaTeX: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1744-1791
		return new MMLmspace( "", [ "linebreak" => "newline" ] );
	}

	public static function dots( $node, $passedArgs, $operatorContent, $name,
		$smth = null, $smth2 = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Dots: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1509-1531
		switch ( $ctx->dotsVariant ?? 'dotso' ) {
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
		$left = null, $right = null, $thick = null, $style = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Genfrac: swh:1:cnt:d1ea310dac6fcde3604a6155f33b8935b1e17349;lines=693-756
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
		$num = new MMLmrow( TexClass::ORD, [], $node->renderArg1( $ctx ) );
		$denom = new MMLmrow( TexClass::ORD, [], $node->renderArg2( $ctx ) );

		$output[] = MMLmfrac::newSubtree( $num, $denom, "", $attrs );
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

	public static function frac( $node, $passedArgs, $operatorContent, $name,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Frac: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=707-713
		if ( $node instanceof Fun2 ) {
			$inner = [ new MMLmrow( TexClass::ORD, [], $node->renderArg1( $ctx ) ),
				new MMLmrow( TexClass::ORD, [], $node->renderArg2( $ctx ) ) ];
		} elseif ( $node instanceof DQ ) {
			$inner = [ new MMLmrow( TexClass::ORD, [], $node->renderBase( $ctx ) ),
				new MMLmrow( TexClass::ORD, [], $node->renderDown( $ctx ) ) ];
		} else {
			$inner = [];
			foreach ( $node->getArgs() as $arg ) {
				$rendered = is_string( $arg ) ? $arg : $node->renderChild( $arg, $ctx );
				$inner[] = new MMLmrow( TexClass::ORD, [], $rendered );
			}
		}
		$mfrac = MMLmfrac::newSubtree( $inner[0], $inner[1] );
		return new MMLmrow( TexClass::ORD, [], $mfrac );
	}

	public static function hline( $node, $passedArgs, $operatorContent, $name,
		$smth1 = null, $smth2 = null, $smth3 = null, $smth4 = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax HLine: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1800-1823
		// HLine is most probably not parsed this way, since only parsed in Matrix context
		return new MMLmrow( "tbd", [], new MMLmtext( "", [], "HLINE TBD" ) );
	}

	public static function hskip( $node, $passedArgs, $operatorContent, $name,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Hskip: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1288-1297
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

	public static function over( $node, $passedArgs, $operatorContent, $name,
		$id = null, $close = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Over: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=677-699
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
			$mfrac = MMLmfrac::newSubtree( new MMLmrow( "", [], $node->renderArg1( $ctx ) ),
				new MMLmrow( "", [], $node->renderArg2( $ctx ) ), "", $attributes );
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
			$rendered = $arg instanceof TexNode ? $node->renderChild( $arg, $ctx ) : $arg;
			$inner[] = new MMLmrow( "", [], $rendered );
		}
		$mfrac = MMLmfrac::newSubtree( $inner[0], $inner[1], "", $attributes );
		if ( $start->isEmpty() ) {
			return $mfrac;
		}
		return new MMLmrow( TexClass::ORD, [], $start, $mfrac, $tail );
	}

	public static function oint( $node, $passedArgs, $operatorContent, $name,
		$uc = null, $attributes = null, $smth2 = null, RenderContext $ctx = new RenderContext()
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

	public static function overset( $node, $passedArgs, $operatorContent, $name,
		$id = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Overset: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=861-871
		if ( $node instanceof DQ ) {
			return new MMLmrow( TexClass::ORD, [],
				MMLmover::newSubtree( new MMLmrow(
					"",
					[],
					$node->renderDown( $ctx ) ),
					$node->renderDown( $ctx )
				)
			);
		}
		return new MMLmrow( TexClass::ORD, [],
			MMLmover::newSubtree(
				new MMLmrow(
					"",
					[],
				$node->renderArg2( $ctx ) ),
				$node->renderArg1( $ctx )
			)
		);
	}

	public static function phantom( $node, $passedArgs, $operatorContent, $name,
		$vertical = null, $horizontal = null, $smh3 = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Phantom: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1161-1179
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
					new MMLmphantom( "", [], $node->renderArg( $ctx ) )
				)
			)
		);
	}

	public static function raiseLower( $node, $passedArgs, $operatorContent, $name,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax RaiseLower: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1232-1254
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
		return new MMLmrow( "", [], new MMLmpadded( "", $args, $node->renderArg2( $ctx ) ) );
	}

	public static function underset( $node, $passedArgs, $operatorContent, $name,
		$smh = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Underset: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=879-889
		$inrow = $node->renderArg2( $ctx );
		$arg1 = $node->renderArg1( $ctx );
		if ( !$inrow->isEmpty() && !$arg1->isEmpty() ) {
			return new MMLmrow( TexClass::ORD, [], MMLmunder::newSubtree( $inrow, $arg1 ) );
		}

		// If there are no two elements in munder, not render munder
		return new MMLmrow( TexClass::ORD, [], $inrow, $arg1 );
	}

	public static function underOver( Fun1 $node, $passedArgs, $operatorContent, $name,
		$operatorSymbol = null, $stack = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax UnderOver: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=839-853
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
		$inner = $node->renderArg( $ctx );
		return new MMLmrow( $texClass, [], $movun::newSubtree( $inner, $mo ) );
	}

	public static function mathFont( $node, $passedArgs, $operatorContent, $name,
		$mathvariant = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax MathFont: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=414-433
		return new MMLmrow( TexClass::ORD, [], $node->renderArg( $ctx ) );
	}

	public static function makeBig( $node, $passedArgs, $operatorContent, $name,
		$texClass = null, $size = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax MakeBig: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1365-1384
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

	public static function machine( $node, $passedArgs, $operatorContent, $name,
		$type = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Machine: swh:1:cnt:0015c31cf8d8eb024c292cc48074d57eb016e139;lines=99-112
		// this could also be shifted to Mhchem::toMMLTree for ce
		// For parsing chem (ce) or ??? (pu)
		return new MMLmrow( "", [], $node->renderArg( $ctx ) );
	}

	public static function setFont( $node, $passedArgs, $operatorContent, $name,
		$variant = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax SetFont: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=442-445
		return self::mathFont( $node, $passedArgs, $operatorContent, $name, $variant, ctx: $ctx );
	}

	public static function spacer( $node, $passedArgs, $operatorContent, $name,
		$withIn = null, $smth2 = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Spacer: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=495-500
		return new MMLmspace( "", [ "width" => MMLutil::round2em( $withIn ) ] );
	}

	public static function smash( $node, $passedArgs, $operatorContent, $name,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Smash: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1187-1205
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

			$inner = $node->renderArg2( $ctx );
		} elseif ( $node instanceof Fun1 ) {
			// Implicitly assume "tb" as default mode
			$mpArgs = [ "height" => "0", "depth" => "0" ];
			$inner = $node->renderArg( $ctx );
		}
		return new MMLmrow( TexClass::ORD, [], new MMLmpadded( "", $mpArgs, $inner ) );
	}

	public static function texAtom( $node, $passedArgs, $operatorContent, $name,
		$texClass = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax TeXAtom: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=922-949
		switch ( $name ) {
			case '\mathbin':
				// no break
			case '\mathop':
				// no break
			case '\mathrel':
				$inner = $node->renderArg( $ctx );
				return new MMLmrow( $texClass, [], $inner );
			default:
				$inner = $node->renderArg( $ctx );
				return new MMLmrow( TexClass::ORD, [], new MMLmrow( $texClass, [], $inner ) );
		}
	}

	public static function hBox( $node, $passedArgs, $operatorContent, $name,
		$smth = null, $font = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax HBox: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1415-1420
		// Only literals like \mbox{\AA} get here: Box renders \mbox, \hbox and \text, Fun1 the text macros
		if ( trim( $name ) === "\\mbox" && isset( $operatorContent['foundOC'] ) ) {
			$op = $operatorContent['foundOC'];
			$macro = TexUtil::getInstance()->nullary_macro_in_mbox( $op ) ?
				/* tested in \MediaWiki\Extension\Math\Tests\WikiTexVC\TexUtilTest::testUnicodeDefined */
				[ TexUtil::getInstance()->unicode_char( $op ) ] :
				TexUtil::getInstance()->identifier( $op );
			$input = $macro[0] ?? $op;
			return new MMLmrow( TexClass::ORD, [], new MMLmo( "", [], MMLutil::uc2xNotation( $input ) ) );
		}
		return MMLmerror::newFromText( "undefined hbox" );
	}

	public static function setStyle( $node, $passedArgs, $operatorContent, $name,
		$smth = null, $smth1 = null, $smth2 = null, RenderContext $ctx = new RenderContext()
	): MMLmrow {
		// MathJax SetStyle: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=456-470
		// Declaration::toMMLTree renders math style declarations; a bare one has nothing to style
		return new MMLmrow();
	}

	public static function not( $node, $passedArgs, $operatorContent, $name,
		$smth = null, $smth1 = null, $smth2 = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Not: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1497-1501
		// This is only tested for \not statement without follow-up parameters
		if ( $node instanceof Literal ) {
			return MMLParsingUtil::createNot();
		}
		return MMLmerror::newFromText( "TBD implement not" );
	}

	public static function vbox( $node, $passedArgs, $operatorContent, $name,
		$smth = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax VBox: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=958-975
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

	public static function sqrt( $node, $passedArgs, $operatorContent, $name,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Sqrt: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=721-737
		// There is an additional argument for the root
		if ( $node instanceof Fun2sq ) {
			// In case of an empty curly add an mrow
			$arg2Rendered = $node->renderArg2( $ctx );
			if ( $arg2Rendered->isEmpty() ) {
				$arg2Rendered = new MMLmrow( TexClass::ORD, [] );
			}
			return new MMLmrow( TexClass::ORD, [],
				MMLmroot::newSubtree(
					$arg2Rendered,
					new MMLmrow( TexClass::ORD, [],
						$node->renderArg1( $ctx )
					)
				)
			);
		}
		// Currently this is own implementation from Fun1.php
		return new MMLmrow( TexClass::ORD, [], // assuming that this is always encapsulated in mrow
			new MMLmsqrt( "", [],
				$node->renderArg( $ctx )
			)
		);
	}

	public static function tilde( $node, $passedArgs, $operatorContent, $name,
		RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax Tilde: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=171-174
		return new MMLmtext( "", [], '&#160;' );
	}

	public static function xArrow( $node, $passedArgs, $operatorContent, $name,
		$chr = null, $l = null, $r = null, RenderContext $ctx = new RenderContext()
	): MMLbase {
		// MathJax xArrow: swh:1:cnt:d1ea310dac6fcde3604a6155f33b8935b1e17349;lines=545-608
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
						$node->renderArg1( $ctx )
					),
					$mspace
				),
				new MMLmpadded( "", $mpaddedArgs,
					$node->renderArg2( $ctx )
				)
			) );

		}
		return MMLmover::newSubtree(
			new MMLmstyle( "", [ "scriptlevel" => "0" ], new MMLmo( Texclass::REL, [], $char ) ),
			new MMLmpadded( "", $mpaddedArgs, $node->renderArg( $ctx ), $mspace )
		);
	}

	/** The invisible function application after a named function like \sin that has an argument */
	public static function getApplyFct( RenderContext $ctx ): MMLbase {
		return $ctx->hasFunctionArgument() ? MMLParsingUtil::renderApplyFunction() : new MMLarray();
	}
}
