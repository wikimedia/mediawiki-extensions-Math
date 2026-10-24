<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Render;

use MediaWiki\Extension\Math\WikiTexVC\Nodes\TexNode;

/**
 * What the rendering of a node depends on beyond the node itself: what its ancestors set and
 * what its neighbours in a group decide.
 */
final class RenderContext {

	// TeX's math styles: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=13568-13572
	public const DISPLAY = 0;
	public const TEXT = 2;
	public const SCRIPT = 4;
	public const SCRIPT_SCRIPT = 6;
	public const CRAMPED = 1;

	/**
	 * @param int $style TeX math style
	 * @param array<string,string> $font mathvariant attributes the letters are set in
	 * @param bool $inMatrix
	 * @param bool $inText
	 * @param bool $squashLiterals
	 * @param bool $inSideset
	 * @param string[] $intentParams
	 * @param array<string,array> $colorDefinitions colors defined with \definecolor before the node
	 * @param bool $negated \not comes before the node
	 * @param bool $namedFunction
	 * @param bool $namedFunctionHasArgument
	 * @param ?string $dotsVariant variant of \dots chosen by the token after it
	 * @param ?TexNode $limitsBase the operator before \limits or \nolimits
	 * @param ?TexNode $sidesetOperator the operator after \sideset
	 * @param bool $beforeRight the group is directly before \right
	 */
	public function __construct(
		public readonly int $style = self::DISPLAY,
		public readonly array $font = [],
		public readonly bool $inMatrix = false,
		public readonly bool $inText = false,
		public readonly bool $squashLiterals = false,
		public readonly bool $inSideset = false,
		public readonly array $intentParams = [],
		public readonly array $colorDefinitions = [],
		public readonly bool $negated = false,
		public readonly bool $namedFunction = false,
		public readonly bool $namedFunctionHasArgument = false,
		public readonly ?string $dotsVariant = null,
		public readonly ?TexNode $limitsBase = null,
		public readonly ?TexNode $sidesetOperator = null,
		public readonly bool $beforeRight = false,
	) {
	}

	/**
	 * The context of an argument: style and font as its parent sets them, the other properties inherited unless
	 * given, and the links of the argument to its neighbours
	 * @param int $style
	 * @param array<string,string> $font
	 * @param bool|null $inMatrix
	 * @param bool|null $inText
	 * @param bool $squashLiterals
	 * @param bool|null $inSideset
	 * @param string[]|null $intentParams
	 * @param array|null $colorDefinitions
	 * @param array $links see TexArray::linkArgs()
	 * @param bool $beforeRight
	 * @return self
	 */
	public function forArgument( int $style, array $font, ?bool $inMatrix = null, ?bool $inText = null,
		bool $squashLiterals = false, ?bool $inSideset = null, ?array $intentParams = null,
		?array $colorDefinitions = null, array $links = [], bool $beforeRight = false
	): self {
		return new self(
			$style,
			$font,
			$inMatrix ?? $this->inMatrix,
			$inText ?? $this->inText,
			$squashLiterals,
			$inSideset ?? $this->inSideset,
			$intentParams ?? $this->intentParams,
			$colorDefinitions ?? $this->colorDefinitions,
			$links['negated'] ?? false,
			$links['namedFunction'] ?? false,
			$links['namedFunctionHasArgument'] ?? false,
			$links['dotsVariant'] ?? null,
			$links['limitsBase'] ?? null,
			$links['sidesetOperator'] ?? null,
			$beforeRight
		);
	}

	private ?self $groupMember = null;

	public function forGroupMember(): self {
		if ( !$this->negated && !$this->namedFunction && !$this->namedFunctionHasArgument
			&& $this->dotsVariant === null && $this->limitsBase === null && $this->sidesetOperator === null
			&& !$this->beforeRight
		) {
			return $this;
		}
		$this->groupMember ??= new self(
			style: $this->style,
			font: $this->font,
			inMatrix: $this->inMatrix,
			inText: $this->inText,
			squashLiterals: $this->squashLiterals,
			inSideset: $this->inSideset,
			intentParams: $this->intentParams,
			colorDefinitions: $this->colorDefinitions,
		);
		return $this->groupMember;
	}

	public function isDisplayStyle(): bool {
		return $this->style < self::TEXT;
	}

	public function getScriptLevel(): int {
		return max( 0, intdiv( $this->style, 2 ) - 1 );
	}

	/** True for a named function like \sin that is followed by its argument */
	public function hasFunctionArgument(): bool {
		return $this->namedFunction && $this->namedFunctionHasArgument;
	}

	/** Style after a style or rule from texutil.json, such as "text" or "sub"; null keeps $style */
	public static function nextStyle( int $style, ?string $name ): int {
		// TeX's style rules: swh:1:cnt:62374028b2c5947fdcec6462027d6a37d1bd8444;lines=13855-13859
		return match ( $name ) {
			null => $style,
			'display' => self::DISPLAY,
			'text' => self::TEXT,
			'script' => self::SCRIPT,
			'scriptscript' => self::SCRIPT_SCRIPT,
			'num' => $style + 2 - 2 * intdiv( $style, 6 ),
			'denom' => 2 * intdiv( $style, 2 ) + self::CRAMPED + 2 - 2 * intdiv( $style, 6 ),
			'sub' => 2 * intdiv( $style, 4 ) + self::SCRIPT + self::CRAMPED,
			'sup' => 2 * intdiv( $style, 4 ) + self::SCRIPT + $style % 2,
		};
	}
}
