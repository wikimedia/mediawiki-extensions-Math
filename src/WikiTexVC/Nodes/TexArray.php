<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use Generator;
use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmsup;
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

	public function checkForStyleArgs( TexNode $node ): ?array {
		if ( $node instanceof Literal ) {
			$name = trim( $node->getArg() );
			switch ( $name ) {
				case "\\displaystyle":
					return [ "displaystyle" => "true", "scriptlevel" => "0" ];
				case "\\scriptstyle":
					return [ "displaystyle" => "false", "scriptlevel" => "1" ];
				case "\\scriptscriptstyle":
					return [ "displaystyle" => "false", "scriptlevel" => "2" ];
				case "\\textstyle":
					return [ "displaystyle" => "false", "scriptlevel" => "0" ];
			}
		}
		return null;
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
			$nextNode instanceof FQ ) {
			return $nextNode;
		}
		return null;
	}

	public function checkForLimits( TexNode $currentNode, ?TexNode $nextNode ): array {
		// Preceding 'lim' in example: "\\lim_{x \\to 2}"
		if ( ( $currentNode instanceof DQ || $currentNode instanceof FQ )
			&& ( $currentNode->containsFunc( "\\lim" ) ||
				$currentNode->containsFunc( '\\varinjlim' ) ) ) {

			$base = $currentNode->getBase();
			if ( $base instanceof TexArray && !$base->isEmpty() ) {
				return [ $base->getArgs()[0], false ];
			} else {
				return [ $base, false ];
			}
		}

		/** Find cases which have preceding Literals with nullary_macro-type operators i.e.:
		 * "\iint\limits_D \, dx\,dy"
		 */
		$tu = TexUtil::getInstance();

		// Check whether the current node is a possible preceding literal
		if ( !(
		// logically superfluous brackets were inserted to improve readability
		( $currentNode instanceof Literal &&
				// Check if the current node is a nullary macro such as \iint, \sum, \prod, etc.
				( $tu->nullary_macro( trim( $currentNode->getArg() ) )
				// or a limit operator
				|| ( trim( $currentNode->getArg() ) == "\\lim" ) ) ) ||
		// or the special case of \operatorname
		( $currentNode instanceof Fun1nb && $currentNode->getFname() == "\\operatorname" ) ||
		// special case of latex_function_names
		( $currentNode instanceof TexArray && $currentNode->getLength() === 2 &&
			$currentNode->first() instanceof Literal &&
			// the parser adds a space after the function name (regardless of the user input
			$currentNode->second() instanceof Literal &&
			// @phan-suppress-next-line PhanUndeclaredMethod
			$currentNode->second()->getArg() === " " &&
			// @phan-suppress-next-line PhanUndeclaredMethod
			$tu->latex_function_names( $currentNode->first()->getArg() )
		) ) ) {
			return [ null, false ];
		}

		// Check whether the next node is a possible limits construct
		if ( !( ( $nextNode instanceof DQ || $nextNode instanceof FQ || $nextNode instanceof UQ )
			&& $nextNode->getBase() instanceof Literal
			&& ( $nextNode->containsFunc( "\\limits" ) || $nextNode->containsFunc( "\\nolimits" ) )
			) ) {
			return [ null, false ];

		}
		return [ $currentNode, true ];
	}

	public function checkForNot( TexNode $currentNode ): bool {
		if ( $currentNode instanceof Literal && trim( $currentNode->getArg() ) == "\\not" ) {
			return true;
		}
		return false;
	}

	/**
	 * Count consecutive apostrophe shorthand nodes following the current node.
	 */
	public function countFollowingPrimes( int $start, array $args ): int {
		$ctr = 0;
		$started = false;
		foreach ( $args as $key => $arg ) {
			if ( !$started ) {
				if ( $key == $start ) {
					$started = true;
				}
				continue;
			}
			if ( $arg instanceof Literal && $arg->getArg() === "'" ) {
				$ctr++;
			} else {
				break;
			}
		}
		return $ctr;
	}

	/**
	 * Normalize a superscript made entirely from explicit \prime commands to
	 * the base used by the apostrophe shorthand and the number of primes.
	 *
	 * @return array{TexNode,int}
	 */
	private function normalizePrimeSuperscript( TexNode $node ): array {
		if ( !( $node instanceof FQ ) || $node instanceof DQ ) {
			return [ $node, 0 ];
		}

		$primeCount = $this->countExplicitPrimes( $node->getUp() );
		if ( $primeCount === 0 ) {
			return [ $node, 0 ];
		}

		if ( $node instanceof UQ ) {
			return [ $node->getBase(), $primeCount ];
		}

		return [ new DQ( $node->getBase(), $node->getDown() ), $primeCount ];
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
		if ( $currentNode instanceof TexArray && count( $currentNode->args ) == 2 ) {
			$tu = TexUtil::getInstance();
			$currentNodeContent = $currentNode[0];
			if ( $currentNodeContent instanceof Literal &&
				$tu->latex_function_names( $currentNodeContent->getArg() ) ) {
				$hasNamedFct = true;
			}
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

	private function squashLiterals( array &$arguments ): void {
		$tmp = '';
		foreach ( $this->args as $arg ) {
			if ( !( $arg instanceof Literal ) ) {
				unset( $arguments[ Tag::CLASSTAG  ] );
				return;
			}
			// Don't squash if there is a macro in the literal
			if ( preg_match( "/[\\\\]/", $arg->getArg() ) ) {
				unset( $arguments[ Tag::CLASSTAG  ] );
				return;
			}
			$tmp .= $arg->getArg();
		}
		$this->args = [ new Literal( $tmp ) ];
		$this->curly = false;
	}

	private function squashNumbers(): void {
		$lastNumber = false;
		foreach ( $this->args as $key => $arg ) {
			// Handle the special case of comma as a decimal separator
			// e.g., 3{,}14}
			if (
				$lastNumber !== false &&
				$arg instanceof TexArray &&
				$arg->isCurly() &&
				!$arg->isEmpty() &&
				$arg->args[0] instanceof Literal &&
				trim( $arg->args[0]->getArg() ) === ","
			) {
				$this->args[$lastNumber]->appendText( ',' );
				unset( $this->args[$key] );
				continue;
			}
			if ( !( $arg instanceof Literal ) || !preg_match( "/^[0-9.]$/", $arg->getArg() ) ) {
				$lastNumber = false;
				continue;
			}
			if ( $lastNumber !== false ) {
				$this->args[$lastNumber]->appendText( $arg->getArg() );
				unset( $this->args[$key] );
			} else {
				$lastNumber = $key;
			}
		}
	}

	/** @inheritDoc */
	public function toMMLTree( $arguments = [], &$state = [] ): MMLbase {
		// Everything here is for parsing displaystyle, probably refactored to WikiTexVC grammar later
		// Root node holding the child nodes.
		$mmlStyles = [ new MMLmrow() ];
		$currentColor = null;

		if ( array_key_exists( 'squashLiterals', $state ) ) {
			$this->squashLiterals( $arguments );
		}
		$this->squashNumbers();
		$skip = 0;
		foreach ( $this->args  as $key => $current ) {
			$next = next( $this->args );
			if ( $skip > 0 ) {
				$skip--;
				continue;
			}
			$next = $next === false ? null : $next;
			[ $current, $explicitPrimeCount ] = $this->normalizePrimeSuperscript( $current );
			// Check for sideset
			$foundSideset = $this->checkForSideset( $current, $next );
			if ( $foundSideset ) {
				$state["sideset"] = $foundSideset;
				// Skipping the succeeding Literal
				$skip++;
			}

			// Check for limits
			$foundLimits = $this->checkForLimits( $current, $next );
			if ( $foundLimits[0] ) {
				$state["limits"] = $foundLimits[0];
				if ( $foundLimits[1] ) {
					continue;
				}
			}

			// Check for Not
			$foundNot = $this->checkForNot( $current );
			if ( $foundNot ) {
				$state["not"] = true;
				continue;
			}

			// Check for prime notation
			$primeCount = $explicitPrimeCount ?: $this->countFollowingPrimes( $key, $this->args );
			if ( $primeCount > 0 ) {
				// Apostrophes are following nodes, while explicit primes were part of the
				// superscript node that has already been replaced above.
				if ( $explicitPrimeCount === 0 ) {
					$skip += $primeCount;
				}
				$state['prime'] = $primeCount;
			}

			// Check if there is a new color definition and add it to state
			$foundColorDef = $this->checkForColorDefinition( $current );
			if ( $foundColorDef ) {
				$state["colorDefinitions"][$foundColorDef["name"]] = $foundColorDef;
				continue;
			}
			// Pass preceding color info to state
			$foundColor = $this->checkForColor( $current );
			if ( $foundColor[0] ) {
				$currentColor = $foundColor[1];
				// Skipping the color element itself for rendering
				continue;
			}
			$styleArguments = $this->checkForStyleArgs( $current );

			// An explicit prime intervenes between the named function and its argument,
			// just like the apostrophe shorthand before it is normalized.
			$namedFunctionNext = $explicitPrimeCount > 0 ? null : $next;
			$foundNamedFct = $this->checkForNamedFctArgs( $current, $namedFunctionNext );
			if ( $foundNamedFct[0] ) {
				$state["foundNamedFct"] = $foundNamedFct;
			}

			if ( $styleArguments ) {
				$state["styleargs"] = $styleArguments;
				$mmlStyles[] = new MMLmstyle( "", $styleArguments );
				if ( $next instanceof TexNode && $next->isCurly() ) {
					// Wrap with style-tags when the next element is a Curly which determines start and end tag.
					$content = $this->createMMLwithContext( $currentColor, $next, $state, $arguments );
					$currentContainer = end( $mmlStyles );
					$currentContainer->addChild( $content );
					unset( $state["styleargs"] );
					$skip++;
				}
			} else {
				// Start the style indicator in cases like \textstyle abc
				$currentContent = $this->createMMLwithContext( $currentColor, $current, $state, $arguments );
				$currentContainer = end( $mmlStyles );
				$currentContainer->addChild( $currentContent );
			}

			unset( $state['foundNamedFct'] );
			unset( $state['not'] );
			unset( $state['limits'] );
		}

		while ( count( $mmlStyles ) > 1 ) {
			$container = array_pop( $mmlStyles );
			$parent = end( $mmlStyles );
			$parent->addChild( $container );
		}
		$output = $mmlStyles[0]->getChildren();
		if ( $this->curly && $this->getLength() > 1 ) {
			return new MMLmrow( TexClass::ORD, [], ...$output );
		}
		// Bug: T417592
		if ( $this->curly && $this->getLength() === 1 && ( $output[0] ?? null ) instanceof MMLmo ) {
			$output[0]->setAttribute( 'lspace', '0' );
			$output[0]->setAttribute( 'rspace', '0' );
		}
		return new MMLarray( ...$output );
	}

	/**
	 * @param string|null $currentColor
	 * @param TexNode $currentNode
	 * @param array &$state
	 * @param array $arguments
	 * @return MMLbase
	 */
	private function createMMLwithContext( ?string $currentColor, TexNode $currentNode, array &$state,
										   array $arguments ): MMLbase {
		$ret = $currentNode->toMMLTree( $arguments, $state );
		$ret = $this->addNot( $state, $ret );
		$ret = $this->addPrimeContext( $state, $ret );

		if ( $currentColor ) {
			if ( array_key_exists( "colorDefinitions", $state )
				&& is_array( $state["colorDefinitions"] )
				&& array_key_exists( $currentColor, $state["colorDefinitions"] ?? [] )
				&& is_array( $state["colorDefinitions"][$currentColor] )
				&& array_key_exists( "hex", $state["colorDefinitions"][$currentColor] )
			) {
				$displayedColor = $state["colorDefinitions"][$currentColor]["hex"];

			} else {
				$resColor = TexUtil::getInstance()->color( ucfirst( $currentColor ) );
				$displayedColor = $resColor ?: $currentColor;
			}
			$ret = new MMLmstyle( "", [ "mathcolor" => $displayedColor ], $ret );
		}
		return $ret;
	}

	/**
	 * Applies the TeX \not overlay to the returned operator node.
	 *
	 * When the parser state contains the 'not' flag and the current result is an <mo> leaf,
	 * this appends U+0338 (COMBINING LONG SOLIDUS OVERLAY) to the operator's text, producing
	 * the MathML representation of "not" operators.
	 *
	 * Otherwise, returns the original node unchanged.
	 */
	public function addNot( array $state, MMLbase $ret ): MMLbase {
		// $state['not'] is set when a preceding \not token was encountered.
		if ( !( $state['not'] ?? false ) || !( $ret instanceof MMLmo ) ) {
			return $ret;
		}
		unset( $state['not'] );

		return $ret->setText( $ret->getText() . '&#x338;' );
	}

	/**
	 * If prime notation was recognized, add the corresponding prime operator
	 * to the MathML and wrap it in an msup element.
	 * @param array &$state state containing the number of primes
	 * @param MMLbase $mml mathml input
	 * @return MMLbase MathML with the prime superscript
	 */
	public function addPrimeContext( array &$state, MMLbase $mml ): MMLbase {
		$primeCount = $state['prime'] ?? 0;
		if ( $primeCount <= 0 ) {
			return $mml;
		}

		unset( $state['prime'] );
		if ( $mml->isEmpty() ) {
			$mml = new MMLmrow();
		}
		$ret = MMLmsup::newSubtree( $mml, new MMLmo( "", [], self::getPrimeContent( $primeCount ) ) );
		if ( ( $state['foundNamedFct'][0] ?? false ) && !( $state['foundNamedFct'][1] ?? true ) ) {
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
