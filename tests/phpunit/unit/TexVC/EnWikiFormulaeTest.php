<?php

namespace MediaWiki\Extension\Math\Tests\TexVC;

use InvalidArgumentException;
use MediaWiki\Extension\Math\TexVC\TexVC;
use MediaWikiUnitTestCase;

/**
 * Currently WIP functionalities of en-wiki-formulae.js testsuite.
 * All assertions are currently deactivated, cause high memory load on CI.
 * These tests can be run locally by enabling the ACTIVE flag.
 * File download of the json-input can be done by running:
 * $ cd maintenance && ./downloadMoreTexVCtests.sh
 * @covers \MediaWiki\Extension\Math\TexVC\Parser
 * @group Stub
 */
class EnWikiFormulaeTest extends MediaWikiUnitTestCase {
	private $ACTIVE = true; # indicate whether this test is active
	private $FILENAME = "en-wiki-formulae.json";
	private $CHUNKSIZE = 1000;
	private $texVCbugs;
	private $knownBad = [];

	/**
	 * Result of testing all cases locally (12.10.22)
	 *  (k) is for known and negative status should not be a problem.
	 *  (i) i for investigate
	 * many of \begin align cases have & and && maybe not escaped probably
	 * @var array hashes of cases with no output
	 */
	private $knownBadHashesPHP = [
		"0270c7af664da7afddcac31d7ac3ad0f", # (i) \begin{alignat}2
		"0448c022977e58b500445d3c92a6579a", # (i)  ρ\colon P \to N
		"0e7c8b2fe70a6310bb546f3506e8c2ae", # (i)  a>b"="\&lt;<
		"15212ec94e20fb0a97994cdee3b47dd8", # (i) \begin{alignat}2 ....
		"17a71941f66112018c433832caa51851", # (i) U(0)=−1
		"208676c3b9c6c696f4640d0e2be00a1c", # (k): i ≠ 0 -> known unicode error
		"267b6df35d46a8a5ef4b910298d1bb16", # (i) \ln N! ≈ N \ln N - N -> probably a unicode error
		"34cd4915dc9878e6a836e546a1725c86", # (i) P_{\text{new link to class $[k]$}} \propto k f(k)
		"3d9052d87c15592c7d4f0bdf832a4f22", # (k): is Log
		"465b6086e24083387f88c34b62bf901d",  # (k): is MathML
		"56bacea25f9704ee4d1294f222236f32", # (k):  5--6++h;[d ... -> input makes no sense
		"6007c325e853ca12cfb61f00f9d36109", # (i): |\pm\rangle= -> probably first char wrong
		"629979cd7de132f3d6884d3c48064c76", # (i): CCAI=D-140.7 ....
		"63ac33a39f3f0f86fd10f2fad9560b1f", # (k) is Wikitext in Math element
		"665cf2ffe7708e3cae870b92deddfb6d", # (i)  \left<|v|\right>
		"6865264da42490a9f5f3a91919586727", # (k) is Wikitext
		"6a844321d746ca110ed895f82c622684", # (i)   \begin{alignat}2 ....
		"6e2ea999c31f52054218db930bd4803d", # (i) \begin{align}[K_c] & =
		"740162aa0b844cc286c4a59c0c81fdfe", # (i)  \begin{array}{|l|l|}
		"7419bdfbb162d8787f942ab5db3c0622", # (k) {{#ifeq: {{{hide}}} | yes |}} -> its a math HTML template
		"7d75d44265d5c5e5f3c8bc4cf017f5f4", # (k) is Wikitext
		"851d5397903761d087cbd6ef7d7f34e3", # (k) is Wikitext
		"84a4ff0236d881b0f8319bb776afdf42", # (k) is MathML
		"866f63344780c4751fe344d954023fc9", # (i)  p_\pi(\boldsymbol\eta|\boldsymbol\chi,\nu)
		"87d00edc79289bf18876a311a4bf6678", # (k) is lots of PHP Code ?!
		"960cf2f7a6a5b4a03beba84473829eb3", # (i) \begin{alignat}2
		"962548e7a2255a587363c376c0dfc94c", # (k) is Wikitext
		"9b4d2a885e7b1844887015037d349bb4", # (k) is JSON and Wikitext
		"9c8492839453496fce8fc4b60b876bbd", # (k) is Wikitext
		"9cd7310f8613eeebb28046da004bc237", # (i) \left[\begin{array}{l,l}  s&t\\u&v \end{array}\right ]
		"a61626300c4de2192cd6ae3154ff2d6b", # (k) is Wikitext
		"a7fe1d7521ea19407abfeb670f84220c", # (k) is MathML
		"ab3321ac1f28e565bf37ed75145ad754", # (k) is an invalid Markup
		"b019dc5b370cd6c7f710a2f146cc0154", # (k) is invalid markup
		"b4d6750841d33ff2462b302eebc15b7f", # (k) is MathML
		"b7187b6940a1e789cae1efd776153fbc", # (k) is MathmL
		"bf76a4641adb9a399d70feec04d37660", # (i) A_iR_j \subseteq A_{i+j} ⊇ R_iA_j
		"c159c37865e286fab2b505de11ad4ce9", # (i) P_{\text{new link to $i$}} \propto k_i
		"c78b9c1f5782f92408115fa6f6390331", # (k) is Markup
		"cfcd208495d565ef66e7dff9f98764da", # (i) input is just: 0 ?!
		"d3152d83fd1079191e4cbd3995470ddf", # (i)  { P_{rad} }   =    { R_0 \left<|v|\right>^2 }
		"d41d8cd98f00b204e9800998ecf8427e", # (k) no input
		"d71bc131df1093246af4b2cab8a3be6a", # (k) is Wikitext
		"e0199f5a37a0ab1813cfd0628f826f80", # (i) S = \left ....
		"e3cc368e634d90cee0694fa0834b39b2", # (i)  \left[\begin{array}{l,l}  s&t\\u&v \end{ar
		"e9f6be4c2ba14f4866fe4263bf5c6a0f", # (i)  \mathcal{G} × \mathcal{H}" probably unicode
		"f31d13eca12a0db895b7062491b44886", # (k) Markdown
		"f338c7dea84103c7be9626def39d1c7f", # (i) Z^{X × Y} probably unicode
		"658f88ad3ea4ff14e7b35b0efda8535e", # (i) no math, wikitext.
		"e5e470bd2eaad8eaa35708534f5927f7", # (i) no math, wikitext
	];

	protected function setUp(): void {
		parent::setUp();

		$this->texVCbugs = [
			// Illegal TeX function: \fint
			"\\fint",

			// Illegal TeX function: \for
			"\\for every",

			// wikitext!
			"</nowiki> tag exists if that was the only help page you read. 
			 If you looked at [[Help:Math]] (also known as [[Help:Displaying a formula]], 
			 [[Help:Formula]] and a bunch of other names), 
			 the first thing it says is \"MediaWiki uses a subset of TeX markup\\\"; 
			 a bit later, under \"[[Help:Math#Syntax|Syntax]]\", 
			 it says \"Math markup goes inside <nowiki><math> ... ",

			// unicode literal: ≠
			"\\frac{a}{b}, a, b \\in \\mathbb{Z}, b ≠ 0",

			// "Command \^ invalid in math mode"
			"\\gamma\\,\\pi\\,\\sec\\^2(\\pi\\,(p-\\tfrac{1}{2}))\\!",

			// html entity
			"\\mathbb{Q} \\big( \\sqrt{1 &ndash; p^2} \\big)",

			// unicode literal: ∈
			"p_k ∈ J",

			// unicode literal: −
			"(r−k)!",

			// anomalous @ (but this is valid in math mode)
			"ckl@ckl",

			// unicode literal: ×
			"u×v",

			// bad {} nesting
			"V_{\\text{in}(t)",

			// Illegal TeX function: \cdotP
			"\\left[\\begin{array}{c} L_R \\\\ L_G \\\\ L_B \\end{array}
			\\right]=\\mathbf{P^{-1}A^{-1}}\\left[\\begin{array}{ccc}R_w/R'_w & 0 & 0 
			\\\\ 0 & G_w/G'_w & 0 \\\\ 0 & 0 & B_w/B'_w\\end{array}\\right]
			\\mathbf{A\\cdotP}\\left[\\begin{array}{c}L_{R'} \\\\ L_{G'} \\\\ L_{B'} \\end{array}\\right]",

			// Illegal TeX function: \colour
			"\\colour{red}test",

			// unicode literal: ½
			"½",

			// unicode literal: …
			"…",

			// Illegal TeX function: \y
			" \\y (s)  ",

			// should be \left\{ not \left{
			"\\delta M_i^{-1} = - \\propto \\sum_{n=1}^N D_i \\left[ n \\right] 
			\\left[ \\sum_{j \\in C \\left{i\\right} } F_{j i} \\left[ n - 1 \\right] + Fext_i 
			\\left[ n^-1 \\right] \\right]",

			// Illegal TeX function: \sout
			"\\sout{4\\pi x}",

			// unicode literal: −
			"~\\sin^{−1} \\alpha",

			// wikitext
			//""</nowiki> and <nowiki>"",

			// unicode literal (?): \201 / \x81
			"\\ x\x81\"=ax+by+k_1",

			// wikitext
			"</nowiki></code> tag does not consistently italicize text which 
			it encloses.  For example, compare \"<math>Q = d",

			// unicode literal: ²
			"x²",

			// Illegal TeX function: \grdot
			"\\grdot",

			// Illegal TeX function: \setin (also missing "}")
			"\\mathbb{\\hat{C}}\\setminus \\overline{\\mathbb{D}} = { w\\setin",

			// unicode literal: −
			"x−y",

			// Illegal TeX function: \spacingcommand
			"\\scriptstyle\\spacingcommand ",

			// unicode literal: π
			"e^{iπ} = \\cos(π) + i\\sin(π) \\!",

			// unicode literal: α
			"sin 2α",

			// unicode literal: ∈
			"\\sum_{v=∈V}^{dv} i",

			// missing \right)
			"Q(x + \\alpha,y + \\beta) = \\sum_{i,j} a_{i,j} 
			\\left( \\sum_u \\begin{pmatrix}i\\\\u\\end{pmatrix} x^u 
			\\alpha^{i-u} \\right) \\left( \\sum_v",

			// missing \left)
			"\\begin{pmatrix}i\\\\v\\end{pmatrix} y^v \\beta^{j-v} \\right)",

			// unicode literal: ₃
			"i₃",

			// unicode literal: ≠
			"x ≠ 0",

			// unicode literals: α, →, β
			"((α → β) → α) → α",

			// unicode literal: −
			"(\\sin(\\alpha))^{−1}\\,",

			// wikitext
			"</nowiki>&hellip;<nowiki>",

			// not enough arguments to \frac
			"K_i = \\gamma^{L} _{i} * P_{i,Sat} \\frac{{P}}",

			// wikitext
			" it has broken spacing -->&nbsp;meters. LIGO should 
			be able to detect gravitational waves as small as <math>h \\approx 5\\times 10^{-22}",

			// not enough arguments
			"\\binom",

			// unicode literal: −
			"\\text {E}=\\text {mgh}=0.1\\times980\\times10^{−2}=0.98\\text {erg}",

			// unicode literals: ⊈, Ō
			"⊈Ō"
		];
	}

	/**
	 * Reads the json file to an object
	 * @throws InvalidArgumentException File with testcases does not exists.
	 * @return array json with testcases
	 */
	private function getJSON() {
		$filePath = __DIR__ . '/' . $this->FILENAME;
		if ( !file_exists( $filePath ) ) {
			throw new InvalidArgumentException( "No testfile found at specified path: " . $filePath );
		}
		$file = file_get_contents( $filePath );
		$json = json_decode( $file, true );
		return $json;
	}

	private function mkgroups( $arr, $n ) {
		$result = [];
		$group = [];
		$seen = [];
		foreach ( $arr as $elem ) {
			if ( array_key_exists( $elem["input"], $seen ) ) {
				continue;
			} else {
				$seen[$elem["input"]] = true;
			}
			array_push( $group, $elem );
			if ( count( $group ) >= $n ) {
				array_push( $result, $group );
				$group = [];
			}
		}
		if ( count( $group ) > 0 ) {
			array_push( $result, $group );
		}
		return $result;
	}

	private function createKnownIssues( &$texVCbugs, &$knownBad ) {
		foreach ( $texVCbugs as $s ) {
			if ( is_string( $s ) ) {
				$s = [ "input" => $s ];
			}
			$knownBad[$s["input"]] = true;

			if ( array_key_exists( "texvc", $s ) ) {
				$texVCbugs[$s["input"]] = true;
			}
		}
	}

	public function testAllEnWikiFormulae() {
		if ( !$this->ACTIVE ) {
			$this->markTestSkipped( "All MediaWiki formulae en test not active and skipped. This is expected." );
		}

		$texVC = new TexVC();
		$groups = $this->mkgroups( $this->getJSON(), $this->CHUNKSIZE );
		$this->createKnownIssues( $this->texVCbugs, $this->knownBad );

		foreach ( $groups as  $group ) {
			foreach ( $group as $testcase ) {
				$title = $testcase["inputhash"];
				$f = $testcase["input"];
				try {
					if ( in_array( $title, $this->knownBadHashesPHP ) ) {
						continue;
					}
					$result = $texVC->check( $testcase["input"], [
						"debug" => false,
						"usemathrm" => false,
						"oldtexvc" => false
					] );

					$good = ( $result["status"] === '+' );

					if ( array_key_exists( $f, $this->knownBad ) ) {
						$this->assertTrue( !$good, $title . " with input: " . $f );
					} else {
						$this->assertTrue( $good,  $title . " with input: " . $f );
						$r1 = $texVC->check( $result["output"] );
						$this->assertEquals( "+", $r1["status"],
							"error rechecking output: " . $f . " -> " . $result["output"] );
					}
				} catch ( PhpPegJs\SyntaxError $ex ) {
					$message = "Syntax error: " . $ex->getMessage() .
						' at line ' . $ex->grammarLine . ' column ' .
						$ex->grammarColumn . ' offset ' . $ex->grammarOffset;

					$this->assertTrue( false,  $message );
				}
			}
		}
	}
}
