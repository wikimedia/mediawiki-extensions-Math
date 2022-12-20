<?php

namespace MediaWiki\Extension\Math\TexVC\MMLmappings\TexConstants;

/**
 * This class contains the string how tags are written
 * Changing the values here removes mathjax specifics.
 * Example: "data-mjx-texclass" to "texClass"
 */
class Tag {
	public const ALIGN = "data-mjx-script-align";
	public const ALTERNATE = "data-mjx-alternate";
	public const SCRIPTTAG = "data-mjx-pseudoscript";
	public const CLASSTAG = "data-mjx-texclass";
	// This is some tag in addition to mathvariant
	public const MJXVARIANT = "data-mjx-variant";
}
