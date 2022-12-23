<?php

namespace MediaWiki\Extension\Math\TexVC\MMLmappings\Util;

use MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants\Tag;
use MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants\Variants;

/**
 * This class contains functionalities for MML-node
 * parsing which can be extracted and are used
 * for multiple functions.
 */
class MMLParsingUtil {

	public static function getFontArgs( $name, $variant, $passedArgs ) {
		$args = [];
		switch ( $name ) {
			case "cal":
			case "mathcal":
				$args = [ Tag::MJXVARIANT => "-tex-calligraphic", "mathvariant" => Variants::SCRIPT ];
				break;
			case "it":
			case "mathit":
				$args = [ Tag::MJXVARIANT => $variant, "mathvariant" => Variants::ITALIC ];
				break;
			case "bf":
			case "mathbf":
				$args = [ "mathvariant" => $variant ];
				break;
			// Sstatements from here come from other fct ok ? otherwise create second fct
			case "textit":
				$args = [ "mathvariant" => Variants::ITALIC ];
				break;
			case "textbf":
				$args = [ "mathvariant" => Variants::BOLD ];
				break;
			case "textsf":
				$args = [ "mathvariant" => Variants::SANSSERIF ];
				break;
			case "texttt":
				$args = [ "mathvariant" => Variants::MONOSPACE ];
				break;
			case "textrm":
				break;
			case "emph":
				// Toggle by passed args in emph
				if ( isset( $passedArgs["mathvariant"] ) ) {
					if ( $passedArgs["mathvariant"] === Variants::ITALIC ) {
						$args = [ "mathvariant" => Variants::NORMAL ];
					}
				} else {
					$args = [ "mathvariant" => Variants::ITALIC ];
				}
				break;
			default:
				$args = [ "mathvariant" => $variant ];

		}
		return $args;
	}
}
