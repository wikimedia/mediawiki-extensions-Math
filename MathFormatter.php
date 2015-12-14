<?php

use DataValues\StringValue;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use ValueFormatters\ValueFormatterBase;
use MathMathML;
use MediaWiki\Logger\LoggerFactory;

// @author Duc Linh Tran, Julian Hilbig, Moritz Schubotz

class MathFormatter implements ValueFormatter {

	private $format;

	/*
	 * Loads format to distinguish the type of formatting
	 */
	public function __construct( $format ) {
		$this->format = $format;
	}

	/*
	 * Formats the tex String.
	 * Plain is used in the value input field of Wikidata
	 * HTML is used in Wikidata, displaying the values of the properties from the item
	 * X-Wiki is used in Wikipedia text articles
	 * @since 0.1
	 *
	 * @param string $value
	 * @global type $wgOut
	 *
	 * @return string
	 * @throws \ValueFormatters\FormattingException
	 */
	public function format( $value ) {
		$tex = $value->getValue();
		if ( strpos( $this->format, "text/plain" ) !== false ) {
			return "$tex";
		} elseif ( strpos( $this->format, "text/x-wiki" ) !== false ) {
			return "<math>$tex</math>";
		} elseif ( strpos( $this->format, "text/html" ) !== false ) {
			$renderer =  new MathMathML( $tex );
			if ( $renderer->checkTex() ) {
				if ( $renderer->render() ){
					LoggerFactory::getInstance( 'Math' )->debug( "Rendering successful. Writing output" );
					return $renderer->getHtmlOutput();
				} else {
					LoggerFactory::getInstance( 'Math' )->warning( "Rendering failed. Printing error message." );
					return $renderer->getLastError();
				}
			} else {
				return $renderer->getLastError();
			}
		} else {
			$unknown = var_export( $this->format, true );
			return "$tex";
		}
	}

	/**
	 * Gets format
	 *
	 * @return format
	 */
	public function getFormat() {
		return $this->format;
	}
}
