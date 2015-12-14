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
	 * Formats the tex string based on the known formats
	 *** text/plain: used in the value input field of Wikidata
	 *** text/x-wiki: used in articles to display text to readers
	 *** text/html: used in Wikidata to display the value of properties
	 *
	 * @param string $value
	 *
	 * @return string
	 * @throws \ValueFormatters\FormattingException
	 */
	public function format( $value ) {
		/*echo '<pre>' . var_export($value, true) . '</pre>';
		echo '<pre>' . var_export($this->format, true) . '</pre>';
		die();*/
		$tex = $value->getValue();
		$log = LoggerFactory::getInstance( 'Math' );

		if ( strpos( $this->format, "text/plain" ) !== false ) {
			return "$tex";
		} elseif ( strpos( $this->format, "text/x-wiki" ) !== false ) {
			return "<math>$tex</math>";
		} elseif ( strpos( $this->format, "text/html" ) !== false ) {
			$renderer =  new MathMathML( $tex );
			if ( $renderer->checkTex() ) {
				if ( $renderer->render() ){
					$log->debug( "Rendering successful. Writing output" );
					return $renderer->getHtmlOutput();
				} else {
					$log->warning( "Rendering failed. Printing error message." );
					return $renderer->getLastError();
				}
			} else {
				return $renderer->getLastError();
			}
		} else {
			// unknown format: show error message
			return "<strong class='error texerror'>"
				. "Unknown format " . $this->format
				. "</strong>";
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
