<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\BaseMethods;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\Util\MMLParsingUtil;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmi;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmn;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmpadded;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmstyle;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class Literal extends TexNode {
	private const CURLY_PATTERN = '/(?<start>[\\a-zA-Z\s]+)\{(?<arg>[^}]+)}/';

	/** @var string */
	private $arg;
	/** @var string[] */
	private $literals;
	/** @var string[] */
	private $extendedLiterals;

	public function __construct( string $arg ) {
		parent::__construct( $arg );
		$this->arg = $arg;
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

	public function changeUnicodeFontInput( string $input, array &$state ): string {
		/**
		 * In some font modifications, it is required to explicitly use Unicode
		 * characters instead of (only) attributes in MathML to indicate the font.
		 * This is mostly because of Chrome behaviour. See: https://phabricator.wikimedia.org/T352196
		 */
		if ( isset( $state["double-struck-literals"] ) ) {
			return MMLParsingUtil::mapToDoubleStruckUnicode( $input );
		} elseif ( isset( $state["calligraphic"] ) ) {
			return MMLParsingUtil::mapToCaligraphicUnicode( $input );
		} elseif ( isset( $state["fraktur"] ) ) {
			return MMLParsingUtil::mapToFrakturUnicode( $input );
		} elseif ( isset( $state["bold"] ) ) {
			return MMLParsingUtil::mapToBoldUnicode( $input );
		}
		return $input;
	}

	public function toMMLTree( array $arguments = [], array &$state = [] ): ?MMLbase {
		if ( is_numeric( $this->arg ) ) {
			return new MMLmn( "", $arguments, $this->changeUnicodeFontInput( $this->arg, $state ) );
		}
		return null;
	}

	/** @inheritDoc */
	public function renderMML( $arguments = [], &$state = [] ) {
		if ( $this->arg === " " ) {
			// Fixes https://gerrit.wikimedia.org/r/c/mediawiki/extensions/Math/+/961711
			// And they creation of empty mo elements.
			return "";
		}
		if ( isset( $state["intent-params"] ) ) {
			foreach ( $state["intent-params"] as $intparam ) {
				if ( $intparam == $this->arg ) {
					$arguments["arg"] = $intparam;
				}
			}
		}

		if ( isset( $state["intent-params-expl"] ) ) {
			$arguments["arg"] = $state["intent-params-expl"];
		}

		$mmlTree = $this->toMMLTree( $arguments, $state );
		if ( $mmlTree !== null ) {
			return (string)$mmlTree;
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
			$mi = new MMLmi();
			return $mi->encapsulateRaw( $operatorContent["foundOC"] );
		}

		$inputP = $input;

		// Sieve for Operators
		$bm = new BaseMethods();
		$noStretchArgs = $arguments;
		// Delimiters and operators should not be stretchy by default when used as literals
		$noStretchArgs['stretchy'] ??= 'false';
		$ret = $bm->checkAndParseOperator( $inputP, $this, $noStretchArgs, $operatorContent, $state, false );
		if ( $ret ) {
			return $ret;
		}
		// Sieve for mathchar07 chars
		$bm = new BaseMethods();
		$ret = $bm->checkAndParseMathCharacter( $inputP, $this, $arguments, $operatorContent, false );
		if ( $ret ) {
			return $ret;
		}

		// Sieve for Identifiers
		$ret = $bm->checkAndParseIdentifier( $inputP, $this, $arguments, $operatorContent, false );
		if ( $ret ) {
			return $ret;
		}
		// Sieve for Delimiters
		$ret = $bm->checkAndParseDelimiter( $input, $this, $noStretchArgs, $operatorContent );
		if ( $ret ) {
			return $ret;
		}

		// Sieve for Makros
		$ret = BaseMethods::checkAndParse( $inputP, $arguments,
		array_merge( $operatorContent ?? [], $state ?? [] ),
		$this, false );
		if ( $ret || $ret === '' ) {
			return $ret;
		}

		// Specific
		if ( !( empty( $state['inMatrix'] ) ) && trim( $this->arg ) === '\vline' ) {
			return $this->createVlineElement();
		}

		if ( !( empty( $state['inHBox'] ) ) ) {
			// No mi, if literal is from HBox
			return $input;
		}

		// If falling through all sieves just create an MI element
		$mi = new MMLmi( "", $arguments );
		return $mi->encapsulateRaw( $this->changeUnicodeFontInput( $input, $state ) ); // $this->arg
	}

	/**
	 * @return string
	 */
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
		if ( preg_match( $regexp, $s ) == 1 ) {
			return [ $s ];
		} elseif ( in_array( $s, $lit, true ) ) {
			return [ $s ];
		} else {
			return [];
		}
	}

	/**
	 * @return string
	 */
	public function createVlineElement(): string {
		$mrow = new MMLmrow();
		$mpAdded = new MMLmpadded( "", [ "depth" => "0", "height" => "0" ] );
		$mStyle = new MMLmstyle( "", [ "mathsize" => "1.2em" ] );
		$mo = new MMLmo( "", [ "fence" => "false", "stretchy" => "false" ] );
		return $mrow->encapsulateRaw( $mpAdded->encapsulateRaw(
			$mStyle->encapsulateRaw( $mo->encapsulateRaw( "|" ) ) ) );
	}

}
