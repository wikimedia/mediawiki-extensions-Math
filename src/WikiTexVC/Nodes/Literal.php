<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseParsing;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\MathVariant;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\Variants;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLutil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLarray;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmn;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmover;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmspace;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtext;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmunder;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;
use MediaWiki\Extension\Math\WikiTexVC\TexVC;
use RuntimeException;

class Literal extends TexNode {
	private const CURLY_PATTERN = '/(?<start>[\\a-zA-Z\s]+)\{(?<arg>[^}]+)}/';

	/** @var string[] */
	private $literals;
	/** @var string[] */
	private $extendedLiterals;

	public function __construct(
		private string $arg,
	) {
		parent::__construct( $arg );
		$this->literals = array_keys( TexUtil::getInstance()->getBaseElements()['is_literal'] );
		$this->extendedLiterals = $this->literals;
		array_push( $this->extendedLiterals, '\\infty', '\\emptyset' );
	}

	/**
	 * Gets the arg of the literal or the part that is before
	 * a curly bracket if the expression contains one and matches
	 * {@link self::CURLY_PATTERN}.
	 *
	 * @return string
	 */
	private function getStart(): string {
		if ( preg_match( self::CURLY_PATTERN, $this->arg, $matches ) ) {
			return $matches['start'];
		}
		return $this->arg;
	}

	/**
	 * If the arg matches {@link self::CURLY_PATTERN}, return the
	 * inner content of the curlies.
	 * For example, for if the arg was a{b} this function returns b.
	 *
	 * @return string|null
	 */
	public function getArgFromCurlies(): ?string {
		if ( preg_match( self::CURLY_PATTERN, $this->arg, $matches ) ) {
			return $matches['arg'];
		}
		return null;
	}

	private function changeUnicodeFontInput( string $input, array &$arguments ): string {
		$variant = MathVariant::removeMathVariantAttribute( $arguments );
		if ( $variant !== 'normal' ) {
			// If the variant is normal, we do not need to change the input.
			return MathVariant::translate(
				$input,
				$variant
			);
		}
		return $input;
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		// Attributes of the element this literal becomes
		$arguments = $ctx->font;
		if ( $this->arg === " " ) {
			// Fixes https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Math/+/961711
			// And they creation of empty mo elements.
			return new MMLarray();
		}
		if ( $this->arg === '\\ ' ) {
			return new MMLmtext( "", [], '&#160;' );
		}
		foreach ( $ctx->intentParams as $intparam ) {
			if ( $intparam == $this->arg ) {
				$arguments["arg"] = $intparam;
			}
		}
		// handle comma as decimal separator https://www.php.net/manual/en/function.is-numeric.php#88041
		if ( ( is_numeric( $this->arg ) || is_numeric( str_replace( ',', '.', $this->arg ) ) )
			&& !$ctx->inText ) {
			if ( ( $arguments['mathvariant'] ?? '' ) === 'italic' ) {
				// If the mathvariant italic does not exist for numbers
				// https://github.com/w3c/mathml/issues/77#issuecomment-2993838911
				$arguments['style'] = trim( ( $arguments['style'] ?? '' ) . ' font-style: italic' );
			}
			$content = $this->changeUnicodeFontInput( $this->arg, $arguments );
			return new MMLmn( "", $arguments, $content );
		}

		// is important to split and find chars within curly and differentiate, see tc 459
		$input = $this->getStart();
		$operatorContent = $this->getArgFromCurlies();
		if ( $operatorContent !== null ) {
			$operatorContent = [ 'foundOC' => $operatorContent ];
		}

		// This is rather a workaround:
		// Sometimes literals from WikiTexVC contain complete \\operatorname {asd} hinted as bug tex-2-mml.json
		if ( str_contains( $input, "\\operatorname" ) ) {
			return new MMLmi( "", [], $operatorContent["foundOC"] );
		}

		$inputP = $input;

		// Sieve for Operators
		$bm = new BaseMethods();
		$noStretchArgs = $arguments;
		// Delimiters and operators should not be stretchy by default when used as literals
		$noStretchArgs['stretchy'] ??= 'false';
		$ret = $bm->checkAndParseOperator( $inputP, $this, $noStretchArgs, $operatorContent, false );
		if ( !$ret->isEmpty() ) {
			return $ret;
		}
		// Sieve for mathchar07 chars
		$bm = new BaseMethods();
		$ret = $bm->checkAndParseMathCharacter( $inputP, $this, $arguments, $operatorContent, false );
		if ( !$ret->isEmpty() ) {
			return $ret;
		}

		// Sieve for Identifiers
		$ret = $this->checkAndParseIdentifier( $inputP, $arguments );
		if ( !$ret->isEmpty() ) {
			return $ret;
		}
		// Sieve for Delimiters
		$ret = $bm->checkAndParseDelimiter( $input, $this, $noStretchArgs, $operatorContent );
		if ( !$ret->isEmpty() ) {
			return $ret;
		}

		$operatorContent ??= [];
		try {
			$cb = $this->getLocalCallback( trim( $inputP ), $arguments, $ctx );
		} catch ( RuntimeException ) {
			// ignore exception
			return new MMLarray();
		}
		if ( !$cb->isEmpty() ) {
			return $cb;
		}
		// Sieve for Makros
		$ret = BaseMethods::checkAndParse( $inputP, $arguments, $operatorContent, $this, ctx: $ctx );
		if ( !( $ret instanceof MMLarray ) || !$ret->isEmpty() ) {
			return $ret;
		}

		// Specific
		if ( $ctx->inMatrix && trim( $this->arg ) === '\vline' ) {
			return $this->createVlineElement();
		}

		$content = $this->changeUnicodeFontInput( $input, $arguments );
		if ( $ctx->inText ) {
			// No mi, if literal is from HBox
			return new MMLmtext( "", [], $content );
		}
		// If falling through all sieves just creates an mi element

		return new MMLmi( "", $arguments, $content );
	}

	private function checkAndParseIdentifier( string $input, array $passedArgs ): MMLbase {
		// @phan-suppress-next-line PhanCoalescingNeverUndefined
		$resIdentifier = TexUtil::getInstance()->identifier( trim( $input ) ) ?? null;
		// If the macro has been found, dynamically call the associated parsing function.
		if ( is_string( $resIdentifier ) ) {
			$resIdentifier = [ $resIdentifier ];
		}

		if ( $resIdentifier == null ) {
			return new MMLarray();
		}
		return $this->parseIdentifier( $passedArgs, $input, MMLutil::uc2xNotation( $resIdentifier[0] ),
			$resIdentifier[1] ?? [] );
	}

	private function parseIdentifier( array $passedArgs, string $name, string $uc, array $attrs = [] ): MMLbase {
		// tbd verify rule: Lowercase name ("operator" instead "Operator") seems to
		// indicate additional italic mathvariant when bold already
		if ( !ctype_upper( $name ) ) {
			if ( isset( $passedArgs['mathvariant'] ) && $passedArgs['mathvariant'] === Variants::BOLD ) {
				$passedArgs['mathvariant'] = $passedArgs['mathvariant'] . "-" . Variants::ITALIC;
			}
		}

		$args = array_merge( $attrs, $passedArgs );

		if ( array_key_exists( "texClass", $args ) ) {
			$args["data-mjx-texclass"] = $args["texClass"];
			unset( $args["texClass"] );
		}

		$uc = $this->changeUnicodeFontInput( $uc, $args );

		return new MMLmi( "", $args, $uc );
	}

	/** @inheritDoc */
	public function getFname(): ?string {
		$name = trim( $this->arg );
		return str_starts_with( $name, '\\' ) ? $name : null;
	}

	public function getArg(): string {
		return $this->arg;
	}

	public function setArg( string $arg ) {
		$this->arg = $arg;
	}

	/**
	 * @return int[]|string[]
	 */
	public function getLiterals(): array {
		return $this->literals;
	}

	/**
	 * @return int[]|string[]
	 */
	public function getExtendedLiterals(): array {
		return $this->extendedLiterals;
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		return $this->getLiteral( $this->literals, '/^([a-zA-Z\']|\\\\int)$/' );
	}

	/** @inheritDoc */
	public function extractSubscripts() {
		return $this->getLiteral( $this->extendedLiterals, '/^([0-9a-zA-Z+\',-])$/' );
	}

	/** @inheritDoc */
	public function getModIdent() {
		if ( $this->arg === '\\ ' ) {
			return [ '\\ ' ];
		}
		return $this->getLiteral( $this->literals, '/^([0-9a-zA-Z\'])$/' );
	}

	private function getLiteral( array $lit, string $regexp ): array {
		$s = trim( $this->arg );
		if ( preg_match( $regexp, $s ) || in_array( $s, $lit, true ) ) {
			return [ $s ];
		}
		return [];
	}

	public function createVlineElement(): MMLbase {
		return new MMLmrow( TexClass::ORD, [],
			new MMLmpadded( "", [ "depth" => "0", "height" => "0" ],
				new MMLmstyle( "", [ "mathsize" => "1.2em" ],
					new MMLmo( "", [ "stretchy" => "false" ], "|" )
				)
			)
		);
	}

	protected function limits(): never {
		throw new RuntimeException( 'limits should not be rendered explicitly' );
	}

	/** MathJax NamedFn: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=583-590 */
	protected function namedFn( RenderContext $ctx, array $passedArgs, string $input ): MMLbase {
		// TexArray::linkArgs() sets whether an argument follows, which gets an apply function
		$applyFct = BaseParsing::getApplyFct( $ctx );
		return new MMLarray( new MMLmi( "", $passedArgs, ltrim( $input, '\\' ) ), $applyFct );
	}

	/** MathJax NamedOp: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=599-617 */
	protected function namedOp( RenderContext $ctx, array $passedArgs, string $input, ?string $uc = null ): MMLbase {
		// TexArray::linkArgs() sets whether an argument follows, which gets an apply function
		$applyFct = BaseParsing::getApplyFct( $ctx );
		return new MMLarray( new MMLmo( "", $passedArgs, $uc ?? ltrim( $input, '\\' ) ), $applyFct );
	}

	/** MathJax \mod: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=700-704 */
	protected function mod(): MMLbase {
		return new MMLmrow( TexClass::ORD, [],
			new MMLmo( "", [ "lspace" => "2.5pt", "rspace" => "2.5pt" ], "mod" ) );
	}

	/**
	 * MathJax \implies: swh:1:cnt:32d2132763b2b01d0a77c2fb37dae43356a863aa;lines=525
	 * MathJax \iff: swh:1:cnt:e9fc665797bcb6bdc58f25de1944c8c68d0fd764;lines=710
	 */
	protected function spacedArrow( RenderContext $ctx, array $passedArgs, string $input ): MMLbase {
		// amsmath \implies and \iff put \; around the arrow; \thickmuskip is 5mu
		// swh:1:cnt:e05c33e5d589cd1cb1ab6d74840bb02ea6f08273;lines=907
		// swh:1:cnt:e05c33e5d589cd1cb1ab6d74840bb02ea6f08273;lines=1288
		// swh:1:cnt:72f94735bba07ecbc4214f3cc409de0e137ff1ee;lines=1576
		$mstyle = new MMLmstyle( "", [ "scriptlevel" => "0" ],
			new MMLmspace( "", [ "width" => MMLutil::round2em( 5 / 18 ) ] ) );
		$arrow = trim( $input ) === '\implies' ? "&#x27F9;" : "&#x27FA;";
		return new MMLarray( $mstyle, new MMLmo( "", [], $arrow ), $mstyle );
	}

	/** MathJax \varliminf, \varlimsup, \varinjlim, \varprojlim: swh:1:cnt:32d2132763b2b01d0a77c2fb37dae43356a863aa;lines=70-79 */
	protected function varlim( RenderContext $ctx, array $passedArgs, string $input ): MMLbase {
		$lim = new MMLmi( "", [], "lim" );
		$inner = match ( trim( $input ) ) {
			'\varlimsup' => MMLmover::newSubtree( $lim, new MMLmo( "", [], "&#x2015;" ), "",
				[ "accent" => "true" ] ),
			'\varliminf' => MMLmunder::newSubtree( $lim, new MMLmo( "", [], "&#x2015;" ), "",
				[ "accentunder" => "true" ] ),
			'\varinjlim' => MMLmunder::newSubtree( $lim, new MMLmo( "", [], "&#x2192;" ) ),
			'\varprojlim' => MMLmunder::newSubtree( $lim, new MMLmo( "", [], "&#x2190;" ) ),
		};
		return new MMLmrow( TexClass::OP, [], $inner );
	}

	/** MathJax 3.2.2 mhchem: swh:1:cnt:951c2129885cc59578e24efb55f897008359a59e;lines=80-83 */
	protected function tripledash(): MMLbase {
		// TODO: MathJax 4 uses the mhchem font glyph U+E410
		// swh:1:cnt:05c966955868ca31932daf7e2f3646202e16c203;lines=137
		return new MMLmo( "", [], "&#x2014;" );
	}

	/** MathJax 3.2.2 mhchem: swh:1:cnt:951c2129885cc59578e24efb55f897008359a59e;lines=73-76 */
	protected function longleftrightarrows(): MMLbase {
		// TODO: MathJax 4 uses the mhchem font glyph U+E42B
		// swh:1:cnt:05c966955868ca31932daf7e2f3646202e16c203;lines=147
		$mover = MMLmover::newSubtree(
			new MMLmrow( TexClass::OP, [],
				new MMLmrow( TexClass::ORD, [],
					new MMLmpadded( "", [ "height" => "0", "depth" => "0" ],
						new MMLmo( "", [ "stretchy" => "false" ], "&#x27F5;" )
					)
				),
				new MMLmspace( "", [ "width" => "0px", "height" => ".25em", "depth" => "0px",
					"mathbackground" => "black" ]
				)
			),
			new MMLmrow( TexClass::ORD, [],
				new MMLmo( "", [ "stretchy" => "false" ], "&#x27F6;" )
			)
		);
		return new MMLarray(
			new MMLmtext( "", [], "&#xA0;" ),
			new MMLmrow( TexClass::REL, [], $mover ) );
	}

	/** MathJax 3.2.2 mhchem: swh:1:cnt:951c2129885cc59578e24efb55f897008359a59e;lines=61-72 */
	protected function longHarpoons( RenderContext $ctx, array $passedArgs, string $input, string $tex ): MMLbase {
		$warnings = [];
		$checkRes = ( new TexVC() )->check( $tex, [ "usemhchem" => true ], $warnings );
		return $checkRes["input"]->toMMLtree();
	}
}
