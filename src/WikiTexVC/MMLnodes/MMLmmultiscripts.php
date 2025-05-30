<?php

namespace MediaWiki\Extension\Math\WikiTexVC\MMLnodes;

/**
 * Presentation MathML 3 Element
 * name: "mmultiscripts"
 * description: "?"
 * category: "?"
 */
class MMLmmultiscripts extends MMLbase {

	public function __construct( string $texclass = "", array $attributes = [] ) {
		parent::__construct( "mmultiscripts", $texclass, $attributes );
	}

	/**
	 * @param MMLbase|string $base Main element being annotated
	 * @param MMLbase|string|null $postsubscript Subscript placed after the base
	 * @param MMLbase|string|null $postsuperscript Superscript placed after the base
	 * @param MMLbase|string|null $presubscript Subscript placed before the base
	 * @param MMLbase|string|null $presuperscript Superscript placed before the base
	 * @param string $texclass Optional TeX class for styling
	 * @param array $attributes Additional HTML attributes for the element
	 * @return static New instance with children ordered as:
	 *                 [base, postsubscript, postsuperscript, presubscript, presuperscript]
	 */
	public static function newSubtree(
		$base,
		$postsubscript = null,
		$postsuperscript = null,
		$presubscript = null,
		$presuperscript = null,
		string $texclass = "",
		array $attributes = []
	) {
		$instance = new self( $texclass, $attributes );
		$children = [ $base ];
		// Handle postscripts (must come in pairs)
		$children[] = $postsubscript;
		$children[] = $postsuperscript;
		// Handle prescripts (requires mprescripts marker)
		$hasPre = $presubscript || $presuperscript;
		if ( $hasPre ) {
			$children[] = new MMLmprescripts();
			$children[] = $presubscript;
			$children[] = $presuperscript;
		}
		$instance->children = $children;
		return $instance;
	}
}
