<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\Math\WikiTexVC\Nodes;

use Generator;
use InvalidArgumentException;
use MediaWiki\Extension\Math\WikiTexVC\MMLmappings\TexConstants\TexClass;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLbase;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmo;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmrow;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtable;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtd;
use MediaWiki\Extension\Math\WikiTexVC\MMLnodes\MMLmtr;
use MediaWiki\Extension\Math\WikiTexVC\Render\RenderContext;
use MediaWiki\Extension\Math\WikiTexVC\TexUtil;

class Matrix extends TexArray {

	private array $lines = [];

	private ?TexArray $columnSpecs = null;

	private ?string $renderedColumSpecs = null;
	private ?array $boarder = null;

	private ?array $alignInfo = null;

	public function __construct(
		private string $top,
		TexArray $mainarg,
		?LengthSpec $rowSpec = null,
	) {
		foreach ( $mainarg->args as $row ) {
			if ( !$row instanceof TexArray ) {
				throw new InvalidArgumentException( 'Nested arguments have to be type of TexArray' );
			}
			$firstCell = $row->first();
			$inner = $firstCell instanceof TexArray ? $firstCell->first() : null;
			$this->lines[] = $inner instanceof Literal && trim( $inner->getArg() ) === '\\hline';
		}
		if ( $mainarg instanceof Matrix ) {
			$this->args = $mainarg->args;
			$this->curly = $mainarg->curly;
		} else {
			parent::__construct( ...$mainarg->args );
		}
		if ( $rowSpec && count( $this->args ) ) {
			// @phan-suppress-next-line PhanUndeclaredMethod
			$this->first()->setRowSpecs( $rowSpec );
		}
	}

	public function getLines(): array {
		return $this->lines;
	}

	public function getTop(): string {
		return $this->top;
	}

	public function setTop( string $top ): Matrix {
		$this->top = $top;
		return $this;
	}

	public function getRenderedColumnSpecs(): string {
		if ( $this->renderedColumSpecs == null ) {
			$this->renderColumnSpecs();
		}
		return $this->renderedColumSpecs;
	}

	public function setColumnSpecs( TexArray $specs ): Matrix {
		$this->columnSpecs = $specs;
		$this->renderedColumSpecs = null;
		$this->alignInfo = null;
		$this->boarder = null;
		return $this;
	}

	public function getAlign(): ?array {
		if ( $this->alignInfo == null ) {
			$this->renderColumnSpecs();
		}
		return $this->alignInfo;
	}

	public function getMainarg(): TexArray {
		return $this;
	}

	/** @inheritDoc */
	public function containsFunc( $target, $args = null ) {
		if ( $args == null ) {
			$args = [
				'\\begin{' . $this->top . '}',
				'\\end{' . $this->top . '}',
				...$this->args,
			];
		}
		return parent::containsFunc( $target, $args );
	}

	/** @inheritDoc */
	public function inCurlies() {
		return $this->render();
	}

	/** @inheritDoc */
	public function render() {
		$colSpecs = $this->columnSpecs !== null ? $this->columnSpecs->render() : '';
		return '{\\begin{' . $this->top . '}' . $colSpecs . $this->renderMatrix( $this ) . '\\end{' .
			$this->top . '}}';
	}

	/** @inheritDoc */
	public function argContext( TexNode $arg, RenderContext $ctx, array $links = [], ?array $colors = null
	): RenderContext {
		// TeX sets each cell as a $...$ formula, which starts in text style; texutil.json lists exceptions.
		// plain.tex \matrix: swh:1:cnt:0f363a96c0e1f93d830e97534b1fe07861fdae69;lines=1093-1096
		// amsmath matrix: swh:1:cnt:d4a287b9531788cd614d524bdd53f38681a63902;lines=2525-2527
		$style = self::macroArgStyle( $this->getTop(), 0, RenderContext::TEXT );
		$inMatrix = $this->definition()['vline'] ?? true;
		return $ctx->forArgument( $style, $ctx->font, inMatrix: $inMatrix,
			squashLiterals: $ctx->squashLiterals, colorDefinitions: $colors, links: $links );
	}

	private function renderMatrix( Matrix $matrix ): string {
		$renderedLines = '';
		for ( $i = 0; $i < count( $matrix->args ); $i++ ) {
			$renderedLines .= self::renderLine( $matrix->args[$i] );
			if ( $i < count( $matrix->args ) - 1 ) {
				// @phan-suppress-next-line PhanTypeMismatchArgumentSuperType
				$renderedLines .= $matrix->renderRowSpec( $matrix->args[$i] );
			}
		}
		return $renderedLines;
	}

	private static function renderLine( TexNode $l ): string {
		$mapped = array_map( static function ( $x ){
			return $x->render();
		}, $l->args );
		return implode( '&', $mapped );
	}

	/** @inheritDoc */
	public function extractIdentifiers( $args = null ) {
		if ( $args == null ) {
			$args = $this->args;
		}

		$mapped = array_map( function ( $a ){
			return array_map( function ( $p ){
				return parent::extractIdentifiers( $p->args );
			}, $a->args );
		}, $args );

		return self::flatDeep( $mapped );
	}

	/**
	 * @param array|string $a
	 * @return array|string
	 */
	private static function flatDeep( $a ) {
		if ( !is_array( $a ) ) {
			return $a;
		}

		$reduced = array_reduce( $a, self::reduceCallback( ... ), [] );
		return $reduced;
	}

	/**
	 * @param array $acc
	 * @param array|string $val
	 * @return array
	 */
	private static function reduceCallback( $acc, $val ) {
		// Casting to array if output is string, this is required for array_merge function.
		$fld = self::flatDeep( $val );
		if ( !is_array( $fld ) ) {
			$fld = [ $fld ];
		}
		return array_merge( $acc, $fld );
	}

	/**
	 * @suppress PhanTypeMismatchReturn
	 * @return Generator<TexArray>
	 */
	public function getIterator(): Generator {
		return parent::getIterator();
	}

	/**
	 * @return void
	 */
	public function renderColumnSpecs(): void {
		$colSpecs = $this->columnSpecs ?? new TexArray();
		$this->renderedColumSpecs = trim( $colSpecs->render(), "{} \n\r\t\v\x00" );
		$colNo = 0;
		$this->boarder = [];
		$definition = $this->definition();
		if ( isset( $definition['align'] ) ) {
			// TODO: aligned would need to guess the number of columns from the content
			$repeat = is_numeric( $this->renderedColumSpecs ) ? (int)$this->renderedColumSpecs
				: $definition['repeat'] ?? 1;
			$this->alignInfo = array_merge( ...array_fill( 0, $repeat, $definition['align'] ) );
			return;
		}
		foreach ( str_split( $this->renderedColumSpecs ) as $chr ) {
			if ( $chr === '|' ) {
				$this->boarder[$colNo] = true;
			}
			if ( in_array( $chr, [ 'r', 'l', 'c' ], true ) ) {
				$this->alignInfo[$colNo] = $chr;
				$colNo++;
			}
		}
	}

	/** The entry of this environment under matrix in texutil.json */
	private function definition(): array {
		return TexUtil::getInstance()->matrix( $this->top ) ?: [];
	}

	public function getBoarder(): array {
		if ( $this->boarder == null ) {
			$this->renderColumnSpecs();
		}
		return $this->boarder;
	}

	public function renderRowSpec( TexArray $row ): string {
		$rowSpecs = '';
		if ( $row->getRowSpecs() !== null ) {
			$rowSpecs = $row->getRowSpecs()->render();
		}
		return '\\\\' . $rowSpecs;
	}

	/** @inheritDoc */
	public function toMMLTree( RenderContext $ctx = new RenderContext() ): MMLbase {
		$definition = $this->definition();
		// MathJax Matrix: swh:1:cnt:0dc9c1b9d75e51da3f0b700e6e3286b5f8072d72;lines=1547-1610
		$resInner = [];
		$boarder = $this->getBoarder();
		$align = $this->getAlign();
		$rowNo = 0;
		$lines = $this->getLines();
		foreach ( $this as $row ) {
			$rowCtx = $this->argContext( $row, $ctx );
			$innerInnter = [];
			$colNo = 0;
			$isEmptyLine = true;
			$rowAttributes = [];
			$rowSpecs = $row->getRowSpecs();
			if ( $rowSpecs ) {
				$rowAttributes['style'] = "padding-bottom: {$rowSpecs->getCssLength()};";
			}
			foreach ( $row  as $cell ) {
				$cellCtx = $row->argContext( $cell, $rowCtx );
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
				$isEmptyLine = $isEmptyLine && $usedArg->isEmpty();
				$innerInnter[] = new MMLmtd( "", $mtdAttributes, $usedArg->toMMLTree( $cellCtx ) );
				$colNo++;
			}
			$rowNo++;
			// empty trailing lines with only one empty cell should not be rendered
			if ( $rowNo === count( $lines ) && $colNo === 1 && $isEmptyLine ) {
				break;
			}
			$resInner[] = new MMLmtr( "", [], ...$innerInnter );
		}
		$mtable = new MMLmtable( '', isset( $definition['class'] ) ? [ 'class' => $definition['class'] ] : [] );
		$mtable->addChild( ...$resInner );
		if ( !isset( $definition['open'] ) ) {
			// Bug T418073
			$mtable->setAttribute( 'displaystyle', 'true' );
			return $mtable;
		}
		$open = new MMLmo( TexClass::OPEN, [], $definition['open'] );
		// MathJax gives an empty closing fence the width of a null delimiter.
		$close = new MMLmo( TexClass::CLOSE,
			[ "data-mwe-fence" => "true", "stretchy" => "true", "symmetric" => "true" ], $definition['close'] );
		return new MMLmrow( TexClass::ORD, [], $open, $mtable, $close );
	}
}
