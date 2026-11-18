<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use Exception;
use InvalidArgumentException;
use LogicException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;
use MediaWiki\Logger\LoggerFactory;

class TexNode {

	/** @var list<self|string> */
	protected $args;

	/**
	 * Creates a TexNode
	 * @param self|string ...$args arguments for this node
	 */
	public function __construct( ...$args ) {
		foreach ( $args as $arg ) {
			if ( !( $arg instanceof self || is_string( $arg ) ) ) {
				throw new InvalidArgumentException( 'Wrong input type specified in args.' );
			}
		}
		$this->args = $args;
	}

	/**
	 * Renders $input with the callback texutil.json names for it, a method of this node or of BaseParsing.
	 * A method of this node gets the context and the further entries of the callback.
	 * @param string $input
	 * @param RenderContext $ctx
	 * @param array $passedArgs attributes for BaseParsing, from Literal
	 * @param array|null $operatorContent content in braces for BaseParsing, from Literal
	 * @return MMLbase
	 */
	protected function parseToMML(
		string $input, RenderContext $ctx, array $passedArgs = [], ?array $operatorContent = []
	): MMLbase {
		[ 'callbackClass' => $class, 'callback' => $method, 'callbackArgs' => $args ] = self::macro( $input );
		if ( $class === static::class ) {
			return $this->{$method}( $ctx, ...$args );
		}
		if ( $class !== null ) {
			throw new LogicException( "Callback to $class::$method should be treated in the respective class." );
		}
		if ( $method !== null ) {
			// The context goes last, by name
			$args['ctx'] = $ctx;
			try {
				return BaseParsing::{$method}( $this, $passedArgs, $operatorContent, $input, ...$args );
			} catch ( Exception $e ) {
				LoggerFactory::getInstance( 'Math' )->error( 'Callback {callback} failed for {input}', [
					'callback' => $method,
					'input' => $input,
					'exception' => $e,
				] );
			}
		}
		$name = strtoupper( self::class );
		return BaseMethods::generateMMLError( "Not implemented $name for $input" );
	}

	/** Name of the macro this node stands for, e.g. \sum or \frac */
	public function getFname(): ?string {
		return null;
	}

	/**
	 * Context of $arg, an argument of this node rendered in $ctx; nodes that change the style, font or another
	 * part of the context for their arguments override this
	 * @param TexNode $arg
	 * @param RenderContext $ctx
	 * @param array $links links of $arg to its neighbours, see TexArray::linkArgs()
	 * @param array|null $colors colors defined before $arg, if not those of $ctx
	 * @return RenderContext
	 */
	public function argContext( TexNode $arg, RenderContext $ctx, array $links = [], ?array $colors = null
	): RenderContext {
		$name = $this->getFname();
		$font = $name === null ? $ctx->font : $this->macroFont( $ctx->font ) ?? $ctx->font;
		return $ctx->forArgument( $this->argStyle( $arg, $ctx->style ), $font, colorDefinitions: $colors,
			links: $links );
	}

	protected function argStyle( TexNode $arg, int $style ): int {
		$name = $this->getFname();
		return $name === null ? $style : self::macroArgStyle( $name, 0, $style );
	}

	public function renderChild( TexNode $arg, RenderContext $ctx ): MMLbase {
		if ( $arg instanceof Literal && $arg->getArg() === "'" ) {
			// An argument that is only an apostrophe, as in a^', is ^\prime on an empty base, like {'}
			return TexArray::addPrimeContext( new MMLmrow(), 1 );
		}
		return $arg->toMMLTree( $this->argContext( $arg, $ctx ) );
	}

	/** @var array<string,array> see macro() */
	private static array $macros = [];

	/**
	 * What texutil.json says about macro $name that matters for its arguments, cached for speed
	 * @param string $name
	 * @return array<string,mixed> callback (the method), callbackClass (null for BaseParsing), callbackArgs,
	 *  textFont, style, argStyles and optArgStyle
	 */
	protected static function macro( string $name ): array {
		if ( !isset( self::$macros[$name] ) ) {
			$tu = TexUtil::getInstance();
			$key = trim( $name );
			$cb = $tu->callback( $key );
			$cb = is_string( $cb ) ? [ $cb ] : ( is_array( $cb ) ? $cb : [ null ] );
			[ $class, $method ] = str_contains( $cb[0] ?? '', '::' ) ? explode( '::', $cb[0] ) : [ null, $cb[0] ];
			self::$macros[$name] = [
				'callback' => $method,
				'callbackClass' => $class,
				'callbackArgs' => array_slice( $cb, 1 ),
				// The text macros like \textbf name their font as the second argument of hBox
				'textFont' => $method === 'hBox' ? $cb[2] ?? null : null,
				'style' => is_string( $tu->style( $key ) ) ? $tu->style( $key ) : null,
				'argStyles' => $tu->arg_styles( $key ) ?: [],
				'optArgStyle' => is_string( $tu->opt_arg_style( $key ) ) ? $tu->opt_arg_style( $key ) : null,
			];
		}
		return self::$macros[$name];
	}

	/** Style of argument $n of macro $name by texutil.json, starting from $style */
	protected static function macroArgStyle( string $name, int $n, int $style ): int {
		$macro = self::macro( $name );
		$style = RenderContext::nextStyle( $style, $macro['style'] );
		return RenderContext::nextStyle( $style, $macro['argStyles'][$n] ?? null );
	}

	/** Attributes of the text of this node, in the font a text macro like \textbf names in texutil.json */
	protected function textFontArgs(): array {
		$font = self::macro( $this->getFname() ?? '' )['textFont'] ?? Variants::NORMAL;
		return $font === Variants::NORMAL ? [] : [ 'mathvariant' => $font ];
	}

	/**
	 * Font that the macro of this node sets for its argument inside $font, or null if it sets none
	 * @param array<string,string> $font
	 * @return array<string,string>|null
	 */
	protected function macroFont( array $font ): ?array {
		$name = $this->getFname() ?? '';
		$macro = self::macro( $name );
		switch ( $macro['callback'] ) {
			case 'mathFont':
			case 'setFont':
				return MMLParsingUtil::getFontArgs( $name, $macro['callbackArgs'][0] ?? null, $font );
			case 'handleOperatorName':
				return array_merge( $font, [ 'mathvariant' => Variants::NORMAL ] );
			case 'hBox':
				// Text is not set in the math font around it; the text macros set their own
				return $this->textFontArgs();
		}
		return null;
	}

	/**
	 * @return self[]|string[]
	 */
	public function getArgs(): array {
		return $this->args;
	}

	/**
	 * @return string
	 */
	public function render() {
		$out = '';
		foreach ( $this->args as $child ) {
			$out .= $child instanceof self ? $child->render() : $child;
		}
		return $out;
	}

	/**
	 * @param RenderContext $ctx context of this node; the default is that of the root of a formula
	 * @return MMLbase
	 */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		return new MMLarray( ...array_map(
			fn ( $child ) => $child instanceof self ? $this->renderChild( $child, $ctx ) : new MMLarray(),
			$this->args
		) );
	}

	/**
	 * @return bool
	 */
	public function isEmpty() {
		foreach ( $this->args ?? [] as $arg ) {
			if ( $arg instanceof self && !$arg->isEmpty() ) {
				return false;
			}
			if ( is_string( $arg ) && $arg !== '' ) {
				return false;
			}
		}
		return true;
	}

	public function getLength(): int {
		return count( $this->args ?? [] );
	}

	/**
	 * Wraps the rendered result in curly brackets.
	 * @return string rendered result in curlies.
	 */
	public function inCurlies() {
		return '{' . $this->render() . '}';
	}

	/**
	 * @param self[]|string[]|null $args
	 * @return string[]
	 */
	public function extractIdentifiers( $args = null ) {
		$output = [];

		foreach ( $args ?? $this->args as $value ) {
			if ( $value instanceof self ) {
				$output = array_merge( $output, $value->extractIdentifiers() );
			} else {
				$output[] = $value;
			}
		}

		return $output;
	}

	/**
	 * @param string|array $target
	 * @param self[]|string[]|null $args
	 * @return bool
	 */
	public function containsFunc( $target, $args = null ) {
		foreach ( $args ?? $this->args as $value ) {
			if ( $value instanceof self ) {
				$ret = $value->containsFunc( $target );
			} else {
				$ret = self::texContainsFunc( $target, $value );
			}
			if ( $ret ) {
				// Do not check the other items, if some function has been found already.
				return true;
			}
		}

		return false;
	}

	/**
	 * @return string|array
	 */
	public function extractSubscripts() {
		return [];
	}

	/**
	 * @return string|array
	 */
	public function getModIdent() {
		return [];
	}

	/**
	 * strings can contain function references only in a few specific
	 * forms, which we test for here.
	 *
	 * @param string|array $target
	 * @param string $t Tex to be checked
	 * @return string|bool rendered LaTeX string or false if not found.
	 */
	public static function texContainsFunc( $target, string $t ) {
		// protect against using random strings as keys in target
		if ( !$t || $t[0] !== '\\' ) {
			return false;
		}

		// may have trailing '(', '[', '\\{' or " "
		$t = preg_replace( '/(\(|\[|\\\\{| )$/', '', $t );

		// special case #1: \\operatorname {someword}
		$m = preg_match( '/^\\\\operatorname \{([^\\\\]*)}$/', $t );
		if ( $m == 1 ) {
			return self::match( $target, '\\operatorname' );
		}

		// special case #2: \\mbox{\\somefunc}
		$matches = [];
		$m = preg_match( '/^\\\\mbox\{(\\\\.*)}$/', $t, $matches );
		if ( $m == 1 ) {
			return self::match( $target, '\\mbox' ) ?: self::match( $target, $matches[1] );
		}

		// special case #3: \\color, \\pagecolor, \\definecolor
		$matches = [];
		$m = preg_match( '/^(\\\\(?:page|define)?color) /', $t, $matches );
		if ( $m == 1 ) {
			return self::match( $target, $matches[1] );
		}

		// special case #4: \\mathbb, \\mathrm
		$matches = [];
		$m = preg_match( '/^(\\\\math..) \{(\\\\.*)}$/', $t, $matches );
		if ( $m == 1 ) {
			return self::match( $target, $matches[1] ) ?: self::match( $target, $matches[2] );
		}

		return self::match( $target, $t );
	}

	/**
	 * Matches a string against a string, array, or set target.
	 * @param string|array $target
	 * @param string $str
	 * @return bool|string matching value or false
	 */
	public static function match( $target, string $str ) {
		if ( is_string( $target ) ) {
			return $target === $str ? $str : false;
		}

		foreach ( $target as $key => $value ) {
			// In javascript both types are used to comparison in match functionality
			if ( is_string( $key ) ) {
				if ( $key === $str ) {
					return $str;
				}
			} elseif ( is_array( $value ) ) {
				if ( self::match( $value, $str ) !== false ) {
					return $str;
				}
			} elseif ( $value === $str ) {
				return $str;
			}
		}

		return false;
	}

	public function isCurly(): bool {
		return false;
	}

}
