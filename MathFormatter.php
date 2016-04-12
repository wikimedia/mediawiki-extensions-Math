<?php

use DataValues\StringValue;
use ValueFormatters\Exceptions\MismatchingDataValueTypeException;
use ValueFormatters\ValueFormatter;
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

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * Loads format to distinguish the type of formatting
	 *
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $format ) {
		switch ( $format ) {
			case SnakFormatter::FORMAT_PLAIN:
			case SnakFormatter::FORMAT_WIKI:
			case SnakFormatter::FORMAT_HTML:
			case SnakFormatter::FORMAT_HTML_DIFF:
			case SnakFormatter::FORMAT_HTML_WIDGET:
				$this->format = $format;
				break;
			default:
				throw new InvalidArgumentException( 'Unsupported output format: ' . $format );
		}
	}

	/**
	 * @param StringValue $value
	 *
	 * @throws MismatchingDataValueTypeException
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new MismatchingDataValueTypeException( 'StringValue', get_class( $value ) );
		}

		$tex = $value->getValue();

		switch ( $this->format ) {
			case SnakFormatter::FORMAT_PLAIN:
				return $tex;
			case SnakFormatter::FORMAT_WIKI:
				return "<math>$tex</math>";
			default:
				$renderer = new MathMathML( $tex );

				if ( $renderer->checkTex() && $renderer->render() ) {
					$html = $renderer->getHtmlOutput();
				} else {
					$html = $renderer->getLastError();
				}

				if ( $this->format === SnakFormatter::FORMAT_HTML_DIFF ) {
					$html = $this->formatDetails( $html, $tex );
				}

				// TeX string is not valid or rendering failed
				return $html;
		}
	}

	/**
	 * Constructs a detailed HTML rendering for use in diff views.
	 *
	 * @param string $valueHtml HTML
	 * @param string $tex TeX
	 *
	 * @return string HTML
	 */
	private function formatDetails( $valueHtml, $tex ) {
		$html = '';
		$html .= Html::rawElement( 'h4',
			[ 'class' => 'wb-details wb-math-details wb-math-rendered' ],
			$valueHtml
		);

		$html .= Html::rawElement( 'div',
			[ 'class' => 'wb-details wb-math-details' ],
			Html::element( 'code', [], $tex )
		);

		return $html;
	}

	/**
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->format;
	}

}
