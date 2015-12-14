<?php

use DataValues\StringValue;
use ValueFormatters\ValueFormatter;
use DataValues\IllegalValueException;

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
	 * Formats the tex string based on the known formats
	 * ** text/plain: used in the value input field of Wikidata
	 * ** text/x-wiki: used in articles to display text to readers
	 * ** text/html: used in Wikidata to display the value of properties
	 * Formats can look like this: "text/html; disposition=widget"
	 * or just "text/plain"
	 *
	 * @param string $value
	 *
	 * @return string
	 * @throws \ValueFormatters\FormattingException
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new IllegalValueException( '$value must be a StringValue' );
		}
		$tex = $value->getValue();

		$clearFormat = array();
		// extract clear format from format-string
		// only formats with prefix "text/" are supported yet
		// keep dash and semicolon in mind
		if ( preg_match( '/text\/([A-Za-z\-]+)(;|$|\s)/', $this->format, $clearFormat ) ) {
			switch ( $clearFormat[1] ) {
				case "plain":
					return "$tex";
				case "x-wiki":
					return "<math>$tex</math>";
				case "html":
					$renderer = new MathMathML( $tex );
					if ( $renderer->checkTex() ) {
						if ( $renderer->render() ) {
							return $renderer->getHtmlOutput();
						}
					}
			}
		}
		// unknown format: return error message
		$msg = wfMessage( 'math-wikidata-unsupported-format', $this->format );
		return "<strong class='error texerror'>$msg</strong>";
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
