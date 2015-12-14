<?php

use DataValues\StringValue;
use ValueFormatters\FormattingException;
use ValueFormatters\ValueFormatter;
use DataValues\IllegalValueException;
use Wikibase\Lib\SnakFormatter;

/*
* Formats the tex string based on the known formats
* * text/plain: used in the value input field of Wikidata
* * text/x-wiki: wikitext
* * text/html: used in Wikidata to display the value of properties
* Formats can look like this: "text/html; disposition=widget"
* or just "text/plain"
*/

class MathFormatter implements ValueFormatter {

	//private $baseFormat;

	/*
	 * Loads format to distinguish the type of formatting
	 */
	public function __construct( $format ) {
		switch ( $format ) {
			case ( SnakFormatter::FORMAT_HTML ):
			case ( SnakFormatter::FORMAT_HTML_DIFF ):
			case ( SnakFormatter::FORMAT_HTML_WIDGET ):
			case ( SnakFormatter::FORMAT_WIKI ):
			case ( SnakFormatter::FORMAT_PLAIN ):
				$this->format = $format;
				break;
			default:
				throw new InvalidArgumentException( 'Unsupported output format: ' . $format);
		}
	}

	/*
	 *
	 * @param StringValue $value
	 *
	 * @return string
	 * @throws \ValueFormatters\FormattingException
	 */
	public function format( $value ) {
		$tex = $value->getValue();

		switch ( $this->format ) {
			case ( SnakFormatter::FORMAT_PLAIN ):
				return "$tex";
			case ( SnakFormatter::FORMAT_WIKI ):
				return "<math>$tex</math>";
			case ( SnakFormatter::FORMAT_HTML ):
			case ( SnakFormatter::FORMAT_HTML_WIDGET ):
			case ( SnakFormatter::FORMAT_HTML_DIFF ):
				$renderer = new MathMathML( $tex );
				if ( $renderer->checkTex() ) {
					if ( $renderer->render() ) {
						return $renderer->getHtmlOutput();
					}
				}
				// TeX string is not valid or rendering failed
				return $renderer->getLastError();
		}
	}

	/**
	 *
	 * @return format
	 */

	public function getFormat() {
		return $this->format;
	}
}
