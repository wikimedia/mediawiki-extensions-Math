<?php 
/**
 * XML syntax and type checker.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

class XmlTypeCheckString extends XmlTypeCheck{
	/**
	 * @param string $string XML string
	 * @param $filterCallback callable (optional)
	 *        Function to call to do additional custom validity checks from the
	 *        SAX element handler event. This gives you access to the element
	 *        namespace, name, and attributes, but not to text contents.
	 *        Filter should return 'true' to toggle on $this->filterMatch
	 */
	function __construct( $string, $filterCallback = null ) {
		$this->filterCallback = $filterCallback;
		$this->run( $string );
	}
	/**
	 * 
	 * @param string $string XML String
	 */
	private function run( $string ) {
		$parser = xml_parser_create_ns( 'UTF-8' );

		// case folding violates XML standard, turn it off
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, false );

		xml_set_element_handler( $parser, array( $this, 'rootElementOpen' ), false );

		$ret = xml_parse( $parser, $string, true );
		return;
		if ( $ret == 0 ) {
			// XML isn't well-formed!
			xml_parser_free( $parser );
			return;
		}

		$this->wellFormed = true;

		xml_parser_free( $parser );

	}
	/**
	 * @param $parser
	 * @param $name
	 * @param $attribs
	 */
	private function rootElementOpen( $parser, $name, $attribs ) {
		$this->rootElement = $name;
	
		if ( is_callable( $this->filterCallback ) ) {
			xml_set_element_handler( $parser, array( $this, 'elementOpen' ), false );
			$this->elementOpen( $parser, $name, $attribs );
		} else {
			// We only need the first open element
			xml_set_element_handler( $parser, false, false );
		}
	}
	
	/**
	 * @param $parser
	 * @param $name
	 * @param $attribs
	 */
	private function elementOpen( $parser, $name, $attribs ) {
		if ( call_user_func( $this->filterCallback, $name, $attribs ) ) {
			// Filter hit!
			$this->filterMatch = true;
		}
	}

}