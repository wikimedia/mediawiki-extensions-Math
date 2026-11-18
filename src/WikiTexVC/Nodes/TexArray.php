<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use Generator;
use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsup;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class TexArray extends TexNode implements \ArrayAccess, \IteratorAggregate {
	protected bool $curly = false;
	private ?LengthSpec $rowSpecs = null;

	/**
	 * @param TexNode|string ...$args
	 * @return self
	 */
	public static function newCurly( ...$args ) {
		$node = new self( ...$args );
		$node->curly = true;
		return $node;
	}

	/** @inheritDoc */
	public function __construct( ...$args ) {
		$nargs = [];

		foreach ( $args as &$arg ) {
			if ( $arg !== null ) {
				array_push( $nargs, $arg );
			}
		}

		self::checkInput( $nargs );
		parent::__construct( ...$nargs );
	}

	/**
	 * Checks if an TexNode of Literal contains color information (color, pagecolor)
	 * and returns info how to continue with the parsing.
	 * @param TexNode $node node to check if it contains color info
	 * @return array index 0: (bool) was color element found, index 1: (string) specified color
	 */
	public function checkForColor( TexNode $node ) {
		if ( $node instanceof Literal ) {
			$name = trim( $node->getArg() );
			if ( str_contains( $name, "\\color" ) ) {
				return [ true, $node->getArgFromCurlies() ];
			} elseif ( str_contains( $name, "\\pagecolor" ) ) {
				return [ true, null ];
			}
		}
		return [ false, null ];
	}

	public function checkForColorDefinition( TexNode $node ): ?array {
		if ( $node instanceof Literal ) {
			$name = trim( $node->getArg() );
			if ( str_contains( $name, "\\definecolor" ) ) {
				return MMLParsingUtil::parseDefineColorExpression( $node->getArg() );
			}
		}
		return null;
	}

	/**
	 * Checks two sequential nodes in TexArray if they contain information on sideset expressions.
	 * @param TexNode $currentNode first node in array to check (for sideset expression)
	 * @param TexNode|null $nextNode second node in array to check (for succeeding operator)
	 * @return TexNode|null the succeeding operator for further Parsing or null if sideset not found or invalid
	 */
	public function checkForSideset( TexNode $currentNode, ?TexNode $nextNode ): ?TexNode {
		if ( !( $currentNode instanceof Fun2nb && $currentNode->getFname() == "\\sideset" ) ) {
			return null;
		}
		if ( $nextNode instanceof Literal ||
			$nextNode instanceof DQ ||
			$nextNode instanceof UQ ||
			$nextNode instanceof FQ ||
			$nextNode instanceof Fun1 ||
			( $nextNode instanceof TexArray && $nextNode->isCurly() ) ) {
			return $nextNode;
		}
		return null;
	}

	public function checkForLimits( TexNode $currentNode, ?TexNode $nextNode ): array {
		// An Op atom followed by \limits or \nolimits with scripts, e.g. "\iint\limits_D".
		// TeX's math_limit_switch: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=22026-22031
		if ( TexUtil::getInstance()->op_limits( $currentNode->getFname() ?? '' ) &&
			$nextNode instanceof FQ &&
			in_array( $nextNode->getBase()->getFname(), [ '\\limits', '\\nolimits' ], true )
		) {
			return [ $currentNode, true ];
		}
		return [ null, false ];
	}

	/**
	 * Classify the token after \dots, which is all amsmath's \mdots@@ looks at.
	 * 'dotsb', 'dotsi' and 'dotso' are the amsmath variants. 'rightdelim' is \dotso
	 * before a token accepted by \rightdelim@, which appends \,.
	 * @see swh:1:cnt:e05c33e5d589cd1cb1ab6d74840bb02ea6f08273;lines=1080-1253
	 * @return string|null null if $currentNode is not \dots
	 */
	public function checkForDots( TexNode $currentNode, ?TexNode $nextNode, bool $beforeRight ): ?string {
		if ( !( $currentNode instanceof Literal && trim( $currentNode->getArg() ) === '\\dots' ) ) {
			return null;
		}
		if ( $nextNode === null ) {
			return $beforeRight ? 'rightdelim' : 'dotso';
		}
		if ( $nextNode instanceof DQ || $nextNode instanceof FQ || $nextNode instanceof UQ ) {
			$nextNode = $nextNode->getBase();
		}
		if ( $nextNode instanceof Literal ) {
			$token = $nextNode->getArg();
		} elseif ( $nextNode instanceof Big || $nextNode instanceof Fun1 || $nextNode instanceof Fun2 ) {
			$token = $nextNode->getFname();
		} else {
			return 'dotso';
		}
		return TexUtil::getInstance()->dots_lookahead( trim( $token ) ) ?: 'dotso';
	}

	public function checkForNot( TexNode $currentNode ): bool {
		if ( $currentNode instanceof Literal && trim( $currentNode->getArg() ) == "\\not" ) {
			return true;
		}
		return false;
	}

	/**
	 * Count consecutive apostrophe shorthand nodes following the current node.
	 * @param int $start
	 * @param list<TexNode> $args
	 * @return int
	 */
	private function countFollowingPrimes( int $start, array $args ): int {
		$ctr = 0;
		$next = $args[$start + 1] ?? null;
		while ( $next instanceof Literal && $next->getArg() === "'" ) {
			$ctr++;
			$next = $args[$start + $ctr + 1] ?? null;
		}
		return $ctr;
	}

	/** Number of \prime in the superscript of $node if it holds nothing else, otherwise 0 */
	private function countPrimeSuperscript( TexNode $node ): int {
		if ( !( $node instanceof FQ ) || $node instanceof DQ ) {
			return 0;
		}
		return $this->countExplicitPrimes( $node->getUp() );
	}

	/**
	 * Count \prime commands in a pure explicit-prime superscript.
	 * Return zero if the superscript contains anything else.
	 */
	private function countExplicitPrimes( TexNode $node ): int {
		if ( $node instanceof Literal ) {
			return trim( $node->getArg() ) === '\\prime' ? 1 : 0;
		}
		if ( !( $node instanceof self ) || $node->isEmpty() ) {
			return 0;
		}

		$count = 0;
		foreach ( $node->getArgs() as $child ) {
			$childCount = $this->countExplicitPrimes( $child );
			if ( $childCount === 0 ) {
				return 0;
			}
			$count += $childCount;
		}
		return $count;
	}

	public function checkForNamedFctArgs( TexNode $currentNode, ?TexNode $nextNode ): array {
		// Check if current node is named function
		$hasNamedFct = false;
		if ( $currentNode instanceof Literal ) {
			$hasNamedFct = (bool)TexUtil::getInstance()->latex_function_names( trim( $currentNode->getArg() ) );
		} elseif ( $currentNode instanceof Fun1nb && $currentNode->getFname() === '\\operatorname' ) {
			$hasNamedFct = true;
		}

		// Check if there is a valid argument as next parameter
		$hasValidParameters = false;
		if ( !$hasNamedFct ) {
			return [ $hasNamedFct, $hasValidParameters ];
		}

		if ( $nextNode && !( $nextNode instanceof Literal && $nextNode->getArg() === "'" ) ) {
			$hasValidParameters = true;
		}

		return [ $hasNamedFct, $hasValidParameters ];
	}

	/** @inheritDoc */
	public function argContext( TexNode $arg, RenderContext $ctx, array $links = [], ?array $colors = null
	): RenderContext {
		if ( !$links && ( $colors === null || $colors === $ctx->colorDefinitions ) ) {
			// Most nodes are members of a group without links to their neighbours
			return $ctx->forGroupMember();
		}
		// \operatorname squashes the literals of its groups
		return $ctx->forArgument( $ctx->style, $ctx->font, squashLiterals: $ctx->squashLiterals,
			colorDefinitions: $colors, links: $links );
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		$args = array_values( $this->args );
		$curly = $this->curly;
		$squashed = false;
		if ( $ctx->squashLiterals ) {
			$literal = $this->toOneLiteral();
			if ( $literal ) {
				$args = [ $literal ];
				$curly = false;
				$squashed = true;
			}
		}
		$args = self::squashNumbers( $args );

		$output = [];
		foreach ( $this->linkArgs( $args, $ctx, $ctx->negated && !$squashed ) as $item ) {
			$output[] = $this->renderPlanItem( $ctx, $item );
		}

		// A math style declaration counts as a member of its group, as when it was a nullary macro.
		$first = $args[0] ?? null;
		$mathStyle = $first instanceof Declaration && $first->setsMathStyle() && $first->getArg()->getLength() > 0;
		$length = count( $args );
		if ( $curly && ( $length > 1 || $mathStyle ) ) {
			return new MMLmrow( TexClass::ORD, [], ...$output );
		}
		// Bug: T417592
		// TeX makes a group an Ord atom, so a lone operator such as {\sum} gets no space around it.
		// TeX's math_group: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=22330-22342
		// MathML Core gives an mo outside its operator dictionary 0.2778em on each side instead.
		if ( $curly && $length === 1 && ( $output[0] ?? null ) instanceof MMLmo ) {
			$output[0]->setAttribute( 'lspace', '0' );
			$output[0]->setAttribute( 'rspace', '0' );
		}
		return new MMLarray( ...$output );
	}

	/**
	 * Settles what depends on the neighbours of each argument: \not, \limits, \sideset, \dots,
	 * primes, colors and named functions.
	 *
	 * @param TexNode[] $args
	 * @param RenderContext $ctx context of this group
	 * @param bool $negateFirst \not came before the group, so its first node is negated
	 * @return list<array{?TexNode,?TexNode,?string,int,array,array}> node in the group, node to render,
	 *  color, primes, colors defined, and the links of the node to render
	 */
	private function linkArgs( array $args, RenderContext $ctx, bool $negateFirst ): array {
		$plan = [];
		$colors = $ctx->colorDefinitions;
		$currentColor = null;
		$negate = false;
		$primes = 0;
		$skip = 0;
		$limitsBase = null;
		foreach ( $args as $i => $current ) {
			$next = $args[$i + 1] ?? null;
			if ( $skip > 0 ) {
				$skip--;
				continue;
			}
			$explicitPrimeCount = $this->countPrimeSuperscript( $current );
			$rendered = $explicitPrimeCount && $current instanceof UQ ? $current->getBase() : $current;
			$links = [];
			if ( $limitsBase ) {
				$links['limitsBase'] = $limitsBase;
				$limitsBase = null;
			}

			$foundSideset = $this->checkForSideset( $rendered, $next );
			if ( $foundSideset ) {
				$links['sidesetOperator'] = $foundSideset;
				// Skipping the succeeding operator
				$skip++;
			}
			if ( $this->checkForLimits( $rendered, $next )[0] && $next ) {
				$limitsBase = $rendered;
				continue;
			}
			$dotsVariant = $this->checkForDots( $rendered, $next, $ctx->beforeRight );
			if ( $dotsVariant !== null ) {
				$links['dotsVariant'] = $dotsVariant;
			}
			if ( $this->checkForNot( $rendered ) ) {
				$negate = true;
				continue;
			}

			// A prime without a base, as in {\displaystyle '}, is ^\prime on an empty base, as in TeX.
			if ( $plan === [] && $rendered instanceof Literal && $rendered->getArg() === "'" ) {
				$count = 1 + $this->countFollowingPrimes( $i, $args );
				$skip += $count - 1;
				$plan[] = [ null, null, null, $count, $colors, [] ];
				continue;
			}

			// Apostrophes are following nodes, while explicit primes were part of the
			// superscript node that is replaced when rendering.
			$primeCount = $explicitPrimeCount ?: $this->countFollowingPrimes( $i, $args );
			if ( $primeCount > 0 ) {
				if ( $explicitPrimeCount === 0 ) {
					$skip += $primeCount;
				}
				$primes = $primeCount;
			}

			$foundColorDef = $this->checkForColorDefinition( $rendered );
			if ( $foundColorDef ) {
				$colors[$foundColorDef["name"]] = $foundColorDef;
				continue;
			}
			$foundColor = $this->checkForColor( $rendered );
			if ( $foundColor[0] ) {
				$currentColor = $foundColor[1];
				// Skipping the color element itself for rendering
				continue;
			}
			// An explicit prime intervenes between the named function and its argument,
			// just like the apostrophe shorthand before it is normalized.
			[ $namedFunction, $hasArgument ] =
				$this->checkForNamedFctArgs( $rendered, $explicitPrimeCount > 0 ? null : $next );
			if ( $namedFunction ) {
				// Only the links that are set, so that most members share one context, see argContext()
				$links['namedFunction'] = true;
				$links['namedFunctionHasArgument'] = $hasArgument;
			}
			// \not before the group negates its first node, unless that is replaced when rendering
			if ( $negate || ( $negateFirst && $i === 0 && $rendered === $current ) ) {
				$links['negated'] = true;
				$negate = false;
			}
			$plan[] = [ $current, $rendered, $currentColor, $primes, $colors, $links ];
			$primes = 0;
		}
		return $plan;
	}

	/**
	 * @param RenderContext $ctx context of this group
	 * @param array{?TexNode,?TexNode,?string,int,array,array} $item see linkArgs()
	 * @return MMLbase
	 */
	private function renderPlanItem( RenderContext $ctx, array $item ): MMLbase {
		[ $current, $rendered, $currentColor, $primes, $colors, $links ] = $item;
		if ( $current === null || $rendered === null ) {
			return self::addPrimeContext( new MMLmrow(), $primes );
		}
		// In a group the first node is negated, see linkArgs()
		$negated = $links['negated'] ?? false;
		$negatesGroup = $negated && $rendered instanceof TexArray
			&& ( $rendered->getArgs()[0] ?? null ) instanceof TexNode;

		if ( $rendered !== $current ) {
			// The base of a superscript of primes, as in f^\prime
			$node = $rendered;
			$nodeCtx = $current->argContext( $rendered, $this->argContext( $current, $ctx, [], $colors ), $links );
		} elseif ( $current instanceof FQ && $this->countPrimeSuperscript( $current ) > 0 ) {
			// Scripts whose superscript is only primes, as in f_x^\prime, lose the superscript
			$node = new DQ( $current->getBase(), $current->getDown() );
			$nodeCtx = $this->argContext( $current, $ctx,
				[ 'negated' => $negated, 'limitsBase' => $links['limitsBase'] ?? null ], $colors );
		} else {
			$node = $current;
			$nodeCtx = $this->argContext( $current, $ctx, $links, $colors );
		}

		$ret = $node->toMMLTree( $nodeCtx );
		if ( $node instanceof TexArray && $node->isCurly() && $node->getLength() === 0 ) {
			// Only {} itself: TeX makes it an empty Ord atom
			// TeX's math_group: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=22330-22342
			$ret = new MMLmrow( TexClass::ORD );
		}
		if ( $negated && !$negatesGroup ) {
			$ret = $this->addNot( $ret );
		}
		$ret = self::addPrimeContext( $ret, $primes,
			( $links['namedFunction'] ?? false ) && !( $links['namedFunctionHasArgument'] ?? false )
				&& $node === $rendered );

		if ( $currentColor ) {
			if ( is_array( $colors[$currentColor] ?? null )
				&& array_key_exists( "hex", $colors[$currentColor] )
			) {
				$displayedColor = $colors[$currentColor]["hex"];

			} else {
				$resColor = TexUtil::getInstance()->color( ucfirst( $currentColor ) );
				$displayedColor = $resColor ?: $currentColor;
			}
			$ret = new MMLmstyle( "", [ "mathcolor" => $displayedColor ], $ret );
		}
		return $ret;
	}

	/**
	 * The letters of this group merged into one literal, as \operatorname and the text macros set them;
	 * null if a member is no plain letter
	 */
	public function toOneLiteral(): ?Literal {
		$tmp = '';
		foreach ( $this->args as $arg ) {
			// Don't squash if there is a macro in the literal
			if ( !( $arg instanceof Literal ) || preg_match( "/[\\\\]/", $arg->getArg() ) ) {
				return null;
			}
			$tmp .= $arg->getArg();
		}
		return new Literal( $tmp );
	}

	/**
	 * Merges the digits of a number into one literal
	 * @param TexNode[] $args
	 * @return TexNode[]
	 */
	private static function squashNumbers( array $args ): array {
		$out = [];
		$lastNumber = null;
		$number = '';
		foreach ( $args as $arg ) {
			// Handle the special case of comma as a decimal separator
			// e.g., 3{,}14}
			if (
				$lastNumber !== null &&
				$arg instanceof TexArray &&
				$arg->isCurly() &&
				!$arg->isEmpty() &&
				self::isComma( $arg->getArgs()[0] )
			) {
				$number .= ',';
				$out[$lastNumber] = new Literal( $number );
				continue;
			}
			if ( !( $arg instanceof Literal ) || !preg_match( "/^[0-9.]$/", $arg->getArg() ) ) {
				$lastNumber = null;
				$out[] = $arg;
				continue;
			}
			if ( $lastNumber !== null ) {
				$number .= $arg->getArg();
				$out[$lastNumber] = new Literal( $number );
			} else {
				$lastNumber = count( $out );
				$number = $arg->getArg();
				$out[] = $arg;
			}
		}
		return $out;
	}

	private static function isComma( TexNode $node ): bool {
		return $node instanceof Literal && trim( $node->getArg() ) === ",";
	}

	/**
	 * Applies the TeX \not overlay to an operator: appends U+0338 (COMBINING LONG SOLIDUS OVERLAY)
	 * to the text of an <mo>, and leaves any other node unchanged.
	 */
	public function addNot( MMLbase $ret ): MMLbase {
		if ( !( $ret instanceof MMLmo ) ) {
			return $ret;
		}
		return $ret->setText( $ret->getText() . '&#x338;' );
	}

	/**
	 * Wraps $mml in an msup with $primeCount primes, followed by an apply function for a named
	 * function without an argument.
	 */
	public static function addPrimeContext( MMLbase $mml, int $primeCount, bool $applyFunction = false ): MMLbase {
		if ( $primeCount <= 0 ) {
			return $mml;
		}
		if ( $mml->isEmpty() ) {
			$mml = new MMLmrow();
		}
		$ret = MMLmsup::newSubtree( $mml, new MMLmo( "", [], self::getPrimeContent( $primeCount ) ) );
		if ( $applyFunction ) {
			return new MMLarray( $ret, MMLParsingUtil::renderApplyFunction() );
		}
		return $ret;
	}

	private static function getPrimeContent( int $primeCount ): string {
		return match ( $primeCount ) {
			2 => '&#x2033;',
			3 => '&#x2034;',
			4 => '&#x2057;',
			default => str_repeat( '&#x2032;', $primeCount ),
		};
	}

	/** @inheritDoc */
	public function inCurlies() {
		if ( isset( $this->args[0] ) && count( $this->args ) == 1 ) {
			return $this->args[0]->inCurlies();
		} else {
			return '{' . parent::render() . '}';
		}
	}

	/** @inheritDoc */
	public function extractSubscripts() {
		$y = [];

		foreach ( $this->args as $x ) {
			$y = array_merge( $y, $x->extractSubscripts() );
		}
		if ( isset( $this->args[0] ) && ( count( $this->args ) == count( $y ) ) ) {
			return implode( '', $y );
		}
		return [];
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = $this->args;
		}
		$list = parent::extractIdentifiers( $args );
		$outpos = 0;
		$offset = 0;
		$int = 0;

		for ( $inpos = 0; $inpos < count( $list ); $inpos++ ) {
			$outpos = $inpos - $offset;
			switch ( $list[$inpos] ) {
				case '\'':
					$list[$outpos - 1] .= '\'';
					$offset++;
					break;
				case '\\int':
					$int++;
					$offset++;
					break;
				case '\\mathrm{d}':
				case 'd':
					if ( $int ) {
						$int--;
						$offset++;
						break;
					}
				// no break
				default:
					if ( isset( $list[0] ) ) {
						$list[$outpos] = $list[$inpos];
					}
			}
		}
		return array_slice( $list, 0, count( $list ) - $offset );
	}

	/** @inheritDoc */
	public function getModIdent() {
		$y = [];

		foreach ( $this->args as $x ) {
			$y = array_merge( $y, $x->getModIdent() );
		}

		if ( isset( $this->args[0] ) && ( count( $this->args ) == count( $y ) ) ) {
			return implode( "", $y );
		}
		return [];
	}

	/**
	 * @param TexNode|string ...$elements
	 */
	public function push( ...$elements ): TexArray {
		self::checkInput( $elements );

		array_push( $this->args, ...$elements );
		return $this;
	}

	public function pop() {
		array_splice( $this->args, 0, 1 );
	}

	/**
	 * @return TexNode|null first value
	 */
	public function first() {
		return $this->args[0] ?? null;
	}

	/**
	 * @return TexNode|null second value
	 */
	public function second() {
		return $this->args[1] ?? null;
	}

	/**
	 * @param TexNode|string ...$elements
	 */
	public function unshift( ...$elements ): TexArray {
		array_unshift( $this->args, ...$elements );
		return $this;
	}

	/**
	 * @throws InvalidArgumentException if args not of correct type
	 * @param TexNode[] $args input args
	 * @return void
	 */
	private static function checkInput( $args ): void {
		foreach ( $args as $arg ) {
			if ( !( $arg instanceof TexNode ) ) {
				throw new InvalidArgumentException( 'Wrong input type specified in input elements.' );
			}
		}
	}

	/** @inheritDoc */
	public function render() {
		if ( $this->curly ) {
			return $this->inCurlies();
		}
		return parent::render();
	}

	public function isCurly(): bool {
		return $this->curly;
	}

	public function setCurly( bool $curly = true ): TexArray {
		$this->curly = $curly;
		return $this;
	}

	/**
	 * @return Generator<TexNode>
	 */
	public function getIterator(): Generator {
		yield from $this->args;
	}

	/**
	 * @return TexNode[]
	 */
	public function getArgs(): array {
		return parent::getArgs();
	}

	/** @inheritDoc */
	public function offsetExists( $offset ): bool {
		return isset( $this->args[$offset] );
	}

	/** @inheritDoc */
	public function offsetGet( $offset ): ?TexNode {
		return $this->args[$offset] ?? null;
	}

	/** @inheritDoc */
	public function offsetSet( $offset, $value ): void {
		if ( !( $value instanceof TexNode ) ) {
			throw new InvalidArgumentException( 'TexArray elements must be of type TexNode.' );
		}
		$this->args[$offset] = $value;
	}

	/** @inheritDoc */
	public function offsetUnset( $offset ): void {
		unset( $this->args[$offset] );
	}

	public function setRowSpecs( ?LengthSpec $r ) {
		$this->rowSpecs = $r;
	}

	public function getRowSpecs(): ?LengthSpec {
		return $this->rowSpecs;
	}
}
