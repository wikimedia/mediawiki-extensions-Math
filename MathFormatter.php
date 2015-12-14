<?php

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;
use MathMathML;

class MathFormatter implements ValueFormatter {

	/*
	 * Loads format to distinguish the type of formatting
	 */
	public function __construct( $format ) {
		$this->Format = $format;
	}

	/*
	 * Formats the tex String.
	 * Plain is used in the value input field of Wikidata
	 * HTML is used in Wikidata, displaying the values of the properties from the item
	 * X-Wiki is used in Wikipedia text articles
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return string
	 * @throws \ValueFormatters\FormattingException
	 */
	public function format( $value ) {
		$tex = $value->getValue();
		if ( strpos( $this->Format, "text/plain" ) !== false ) {
			return "$tex";
		} elseif ( strpos( $this->Format, "text/x-wiki" ) !== false ) {
			return "<math>$tex</math>";
		} elseif ( strpos( $this->Format, "text/html" ) !== false ) {
			$renderer =  new MathMathML( $tex );
			var_dump( $renderer->getHtmlOutput() );
			if ( $renderer->checkTex() ) {
				return $renderer->getHtmlOutput();
			} else {
				return "$tex";
			}
		} else {
			$unknown = var_dump( $format );
			return "$tex ( $unkown )";
		}
	}
}
