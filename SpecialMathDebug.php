<?php
class SpecialMathDebug extends SpecialPage {


	function __construct() {
		parent::__construct( 'Math', 'siteadmin' );
	}
	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 */
	function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->setPageTitle( $this->getDescription() );
	}
	function execute( $par ) {
		global $wgDebugMath;
		$output = $this->getOutput();
		$this->setHeaders();
		if ( $wgDebugMath ) {
			if (  !$this->userCanExecute( $this->getUser() )  ) {
				$this->displayRestrictionError();
				return;
			} else {
				$this->testParser();
			}
		} else {
			$output->addWikiText( '\'\'\'This page is avaliblible in math debug mode only.\'\'\'' . "\n\n" .
				'Enable the math debug mode by setting <code> $wgDebugMath = true</code> .' );
		}
	}
	function testParser() {
		$out = $this->getOutput();
		$out->addModules( array( 'ext.math.mathjax.enabler' ));
		foreach ( self::testQuery() as $t ) {
			$out->addWikiText( "MW_MATH_SOURCE:" . str_replace('class="tex"','class="-NO-JAX-"',MathRenderer::renderMath( $t, array(), MW_MATH_SOURCE )) );
			$out->addWikiText( "MW_MATH_PNG:", false );
			$out->addHTML( MathRenderer::renderMath( $t, array(), MW_MATH_PNG ) . "<br \>" );
			$out->addWikiText( "MW_MATH_MATHJAX:", false );
			$out->addHTML( MathRenderer::renderMath( $t, array(), MW_MATH_MATHJAX ) );
		}
	}

	private static function testQuery() {
		return array( 'math_tex' => '\\int_a^b x^{5x dx',
				'e^{i \\pi} + 1 = 0\\,\\!',
				'\\definecolor{red}{RGB}{255,0,0}\\pagecolor{red}e^{i \\pi} + 1 = 0\\,\\!',
				'\\sqrt{\\pi}',
				'\\text{abc}',
				'\\text {abcdefghijklmnopqrstuvwxyzàáâãäåæçčďèéěêëìíîïňñòóôõöřšť÷øùúůûüýÿž}',
				'\\text {abcdefghijklmnopqrstuvwxyzàáâãäåæçčďèéěêëìíîïňñòóôõöřšť÷øùúůûüýÿž}\\,',
				'\\mbox {abcdefghijklmnopqrstuvwxyzàáâãäåæçčďèéěêëìíîïňñòóôõöřšť÷øùúůûüýÿž}',
				'\\mbox {abcdefghijklmnopqrstuvwxyzàáâãäåæçčďèéěêëìíîïňñòóôõöřšť÷øùúůûüýÿž}\\,',
				'\\mbox {ð}',
				'\\mbox {þ}',
				'\\alpha\\,\\!',
				' f(x) = x^2\\,\\!',
				'\\sqrt{2}',
				'\\sqrt{1-e^2}\\!',
				'',
				'\\dot{a}, \\ddot{a}, \\acute{a}, \\grave{a} \\!',
				'\\check{a}, \\breve{a}, \\tilde{a}, \\bar{a} \\!',
				'\\hat{a}, \\widehat{a}, \\vec{a} \\!',
				'\\exp_a b = a^b, \\exp b = e^b, 10^m \\!',
				'\\ln c, \\lg d = \\log e, \\log_{10} f \\!',
				'\\sin a, \\cos b, \\tan c, \\cot d, \\sec e, \\csc f\\!',
				'\\arcsin h, \\arccos i, \\arctan j \\!',
				'\\sinh k, \\cosh l, \\tanh m, \\coth n \\!',
				'\\operatorname{sh}\\,k, \\operatorname{ch}\\,l, \\operatorname{th}\\,m, \\operatorname{coth}\\,n \\!',
				'\\operatorname{argsh}\\,o, \\operatorname{argch}\\,p, \\operatorname{argth}\\,q \\!',
				'\\sgn r, \\left\\vert s \\right\\vert \\!',
				'\\min(x,y), \\max(x,y) \\!',
				'\\min x, \\max y, \\inf s, \\sup t \\!',
				'\\lim u, \\liminf v, \\limsup w \\!',
				'\\dim p, \\deg q, \\det m, \\ker\\phi \\!',
				'\\Pr j, \\hom l, \\lVert z \\rVert, \\arg z \\!',
				'dt, \\operatorname{d}\\!t, \\partial t, \\nabla\\psi\\!',
				'dy/dx, \\operatorname{d}\\!y/\\operatorname{d}\\!x, {dy \\over dx}, {\\operatorname{d}\\!y\\over\\operatorname{d}\\!x}, {\\partial^2\\over\\partial x_1\\partial x_2}y \\!',
				'\\prime, \\backprime, f^\\prime, f\', f\'\', f^{(3)} \\!, \\dot y, \\ddot y',
				'\\infty, \\aleph, \\complement, \\backepsilon, \\eth, \\Finv, \\hbar \\!',
				'\\Im, \\imath, \\jmath, \\Bbbk, \\ell, \\mho, \\wp, \\Re, \\circledS \\!',
				's_k \\equiv 0 \\pmod{m} \\!',
				'a\\,\\bmod\\,b \\!',
				'\\gcd(m, n), \\operatorname{lcm}(m, n)',
				'\\mid, \\nmid, \\shortmid, \\nshortmid \\!',
				'\\surd, \\sqrt{2}, \\sqrt[n]{}, \\sqrt[3]{x^3+y^3 \\over 2} \\!',
				'+, -, \\pm, \\mp, \\dotplus \\!',
				'\\times, \\div, \\divideontimes, /, \\backslash \\!',
				'\\cdot, * \\ast, \\star, \\circ, \\bullet \\!',
				'\\boxplus, \\boxminus, \\boxtimes, \\boxdot \\!',
				'\\oplus, \\ominus, \\otimes, \\oslash, \\odot\\!',
				'\\circleddash, \\circledcirc, \\circledast \\!',
				'\\bigoplus, \\bigotimes, \\bigodot \\!',
				'\\{ \\}, \\O \\empty \\emptyset, \\varnothing \\!',
				'\\in, \\notin \\not\\in, \\ni, \\not\\ni \\!',
				'\\cap, \\Cap, \\sqcap, \\bigcap \\!',
				'\\cup, \\Cup, \\sqcup, \\bigcup, \\bigsqcup, \\uplus, \\biguplus \\!',
				'\\setminus, \\smallsetminus, \\times \\!',
				'\\subset, \\Subset, \\sqsubset \\!',
				'\\supset, \\Supset, \\sqsupset \\!',
				'\\subseteq, \\nsubseteq, \\subsetneq, \\varsubsetneq, \\sqsubseteq \\!',
				'\\supseteq, \\nsupseteq, \\supsetneq, \\varsupsetneq, \\sqsupseteq \\!',
				'\\subseteqq, \\nsubseteqq, \\subsetneqq, \\varsubsetneqq \\!',
				'\\supseteqq, \\nsupseteqq, \\supsetneqq, \\varsupsetneqq \\!',
				'=, \\ne \\neq, \\equiv, \\not\\equiv \\!',
				'\\doteq, \\overset{\\underset{\\mathrm{def}}{}}{=}, := \\!',
				'\\sim, \\nsim, \\backsim, \\thicksim, \\simeq, \\backsimeq, \\eqsim, \\cong, \\ncong \\!',
				'\\approx, \\thickapprox, \\approxeq, \\asymp, \\propto, \\varpropto \\!',
				'<, \\nless, \\ll, \\not\\ll, \\lll, \\not\\lll, \\lessdot \\!',
				'>, \\ngtr, \\gg, \\not\\gg, \\ggg, \\not\\ggg, \\gtrdot \\!',
				'\\le \\leq, \\lneq, \\leqq, \\nleqq, \\lneqq, \\lvertneqq \\!',
				'\\ge \\geq, \\gneq, \\geqq, \\ngeqq, \\gneqq, \\gvertneqq \\!',
				'\\lessgtr \\lesseqgtr \\lesseqqgtr \\gtrless \\gtreqless \\gtreqqless \\!',
				'\\leqslant, \\nleqslant, \\eqslantless \\!',
				'\\geqslant, \\ngeqslant, \\eqslantgtr \\!',
				'\\lesssim, \\lnsim, \\lessapprox, \\lnapprox \\!',
				' \\gtrsim, \\gnsim, \\gtrapprox, \\gnapprox \\,',
				'\\prec, \\nprec, \\preceq, \\npreceq, \\precneqq \\!',
				'\\succ, \\nsucc, \\succeq, \\nsucceq, \\succneqq \\!',
				'\\preccurlyeq, \\curlyeqprec \\,',
				'\\succcurlyeq, \\curlyeqsucc \\,',
				'\\precsim, \\precnsim, \\precapprox, \\precnapprox \\,',
				'\\succsim, \\succnsim, \\succapprox, \\succnapprox \\,',
				'\\parallel, \\nparallel, \\shortparallel, \\nshortparallel \\!',
				'\\perp, \\angle, \\sphericalangle, \\measuredangle, 45^\\circ \\!',
				'\\Box, \\blacksquare, \\diamond, \\Diamond \\lozenge, \\blacklozenge, \\bigstar \\!',
				'\\bigcirc, \\triangle \\bigtriangleup, \\bigtriangledown \\!',
				'\\vartriangle, \\triangledown\\!',
				'\\blacktriangle, \\blacktriangledown, \\blacktriangleleft, \\blacktriangleright \\!',
				'\\forall, \\exists, \\nexists \\!',
				'\\therefore, \\because, \\And \\!',
				'\\or \\lor \\vee, \\curlyvee, \\bigvee \\!',
				'\\land',
				'\\wedge,',
				'\\curlywedge,',
				'\\bigwedge',
				'\\!',
				'\\bar{q}, \\overline{q}, \\lnot \\neg, \\not\\operatorname{R}, \\bot, \\top\\!',
				'\\vdash \\dashv, \\vDash, \\Vdash, \\models \\!',
				'\\Vvdash \\nvdash \\nVdash \\nvDash \\nVDash \\!',
				'\\ulcorner \\urcorner \\llcorner \\lrcorner \\,',
				'\\Rrightarrow, \\Lleftarrow \\!',
				'\\Rightarrow, \\nRightarrow, \\Longrightarrow \\implies\\!',
				'\\Leftarrow, \\nLeftarrow, \\Longleftarrow \\!',
				'\\Leftrightarrow, \\nLeftrightarrow, \\Longleftrightarrow \\iff \\!',
				'\\Uparrow, \\Downarrow, \\Updownarrow \\!',
				'\\rightarrow \\to, \\nrightarrow, \\longrightarrow\\!',
				'\\leftarrow \\gets, \\nleftarrow, \\longleftarrow\\!',
				'\\leftrightarrow, \\nleftrightarrow, \\longleftrightarrow \\!',
				'\\nearrow, \\swarrow, \\nwarrow, \\searrow \\!',
				'\\mapsto, \\longmapsto \\!',
				'\\rightharpoonup \\rightharpoondown \\leftharpoonup \\leftharpoondown \\upharpoonleft \\upharpoonright \\downharpoonleft \\downharpoonright \\rightleftharpoons \\leftrightharpoons \\,\\!',
				'\\curvearrowleft \\circlearrowleft \\Lsh \\upuparrows \\rightrightarrows \\rightleftarrows \\rightarrowtail \\looparrowright \\,\\!',
				'\\curvearrowright \\circlearrowright \\Rsh \\downdownarrows \\leftleftarrows \\leftrightarrows \\leftarrowtail \\looparrowleft \\,\\!',
				'\\hookrightarrow \\hookleftarrow \\multimap \\leftrightsquigarrow \\rightsquigarrow \\twoheadrightarrow \\twoheadleftarrow \\!',
				'\\amalg',
				' \\P',
				' \\S',
				' \\%',
				' \\dagger',
				' \\ddagger ',
				'\\ldots',
				' \\cdots',
				'\\smile \\frown \\wr \\triangleleft \\triangleright\\!',
				'\\diamondsuit, \\heartsuit, \\clubsuit, \\spadesuit, \\Game, \\flat, \\natural, \\sharp \\!',
				'\\diagup \\diagdown \\centerdot \\ltimes \\rtimes \\leftthreetimes \\rightthreetimes \\!',
				'\\eqcirc \\circeq \\triangleq \\bumpeq \\Bumpeq \\doteqdot \\risingdotseq \\fallingdotseq \\!',
				'\\intercal \\barwedge \\veebar \\doublebarwedge \\between \\pitchfork \\!',
				'\\vartriangleleft \\ntriangleleft \\vartriangleright \\ntriangleright \\!',
				'\\trianglelefteq \\ntrianglelefteq \\trianglerighteq \\ntrianglerighteq \\!',
				'a^2',
				'a^2 \\,\\!',
				'a_2',
				'a_2 \\,\\!',
				'10^{30} a^{2+2}',
				'10^{30} a^{2+2}\\,\\!',
				'a_{i,j} b_{f\'}',
				'a_{i,j} b_{f\'}\\,\\!',
				'x_2^3 \\,\\!',
				'{x_2}^3 \\,\\!',
				'10^{10^{8}} \\,\\!',
				'\\sideset{_1^2}{_3^4}\\prod_a^b \\,\\!',
				'{}_1^2\\!\\Omega_3^4 \\,\\!',
				'\\overset{\\alpha}{\\omega} \\,\\!',
				'\\underset{\\alpha}{\\omega} \\,\\!',
				'\\overset{\\alpha}{\\underset{\\gamma}{\\omega}} \\,\\!',
				'\\stackrel{\\alpha}{\\omega} \\,\\!',
				'x\', y\'\', f\', f\'\'',
				'x\', y\'\', f\', f\'\' \\!',
				'x^\\prime, y^{\\prime\\prime}',
				'x^\\prime, y^{\\prime\\prime} \\!',
				'x\\prime, y\\prime\\prime',
				'x\\prime, y\\prime\\prime \\!',
				'\\dot{x}, \\ddot{x}',
				' \\hat a \\ \\bar b \\ \\vec c',
				' \\overrightarrow{a b} \\ \\overleftarrow{c d} \\ \\widehat{d e f}',
				' \\overline{g h i} \\ \\underline{j k l}',
				'\\overset{\\frown} {AB}',
				' A \\xleftarrow{n+\\mu-1} B \\xrightarrow[T]{n\\pm i-1} C',
				'\\overbrace{ 1+2+\\cdots+100 }^{5050}',
				'\\underbrace{ a+b+\\cdots+z }_{26}',
				'\\sum_{k=1}^N k^2',
				'\\textstyle \\sum_{k=1}^N k^2',
				'\\frac{\\sum_{k=1}^N k^2}{a}',
				'\\frac{\\displaystyle \\sum_{k=1}^N k^2}{a}',
				'\\prod_{i=1}^N x_i',
				'\\textstyle \\prod_{i=1}^N x_i',
				'\\coprod_{i=1}^N x_i',
				'\\textstyle \\coprod_{i=1}^N x_i',
				'\\lim_{n \\to \\infty}x_n',
				'\\textstyle \\lim_{n \\to \\infty}x_n',
				'\\int\\limits_{1}^{3}\\frac{e^3/x}{x^2}\\, dx',
				'\\int_{1}^{3}\\frac{e^3/x}{x^2}\\, dx',
				'\\textstyle \\int\\limits_{-N}^{N} e^x\\, dx',
				'\\textstyle \\int_{-N}^{N} e^x\\, dx',
				'\\iint\\limits_D \\, dx\\,dy',
				'\\iiint\\limits_E \\, dx\\,dy\\,dz',
				'\\iiiint\\limits_F \\, dx\\,dy\\,dz\\,dt',
				'\\int_{(x,y)\\in C} x^3\\, dx + 4y^2\\, dy',
				'\\oint_{(x,y)\\in C} x^3\\, dx + 4y^2\\, dy',
				'\\bigcap_{i=_1}^n E_i',
				'\\bigcup_{i=_1}^n E_i',
				'\\frac{2}{4}=0.5',
				'\\tfrac{2}{4} = 0.5',
				'\\dfrac{2}{4} = 0.5 \\qquad \\dfrac{2}{c + \\dfrac{2}{d + \\dfrac{2}{4}}} = a',
				'\\cfrac{2}{c + \\cfrac{2}{d + \\cfrac{2}{4}}} = a',
				'\\cfrac{x}{1 + \\cfrac{\\cancel{y}}{\\cancel{y}}} = \\cfrac{x}{2}',
				'\\binom{n}{k}',
				'\\tbinom{n}{k}',
				'\\dbinom{n}{k}',
				'\\begin{matrix} x & y \\\\ z & v
\\end{matrix}',
				'\\begin{vmatrix} x & y \\\\ z & v
\\end{vmatrix}',
				'\\begin{bmatrix} 0 & \\cdots & 0 \\\\ \\vdots
& \\ddots & \\vdots \\\\ 0 & \\cdots &
0\\end{bmatrix} ',
				'\\begin{Bmatrix} x & y \\\\ z & v
\\end{Bmatrix}',
				'\\begin{pmatrix} x & y \\\\ z & v
\\end{pmatrix}',
				'
\\bigl( \\begin{smallmatrix}
 a&b\\\\ c&d
\\end{smallmatrix} \\bigr)
',
				'f(n) =
\\begin{cases}
 n/2, & \\text{if }n\\text{ is even} \\\\
 3n+1, & \\text{if }n\\text{ is odd}
\\end{cases} ',
				'
\\begin{align}
 f(x) & = (a+b)^2 \\\\
 & = a^2+2ab+b^2 \\\\
\\end{align}
',
				'
\\begin{alignat}{2}
 f(x) & = (a-b)^2 \\\\
 & = a^2-2ab+b^2 \\\\
\\end{alignat}
',
				'\\begin{array}{lcl}
 z & = & a \\\\
 f(x,y,z) & = & x + y + z
\\end{array}',
				'\\begin{array}{lcr}
 z & = & a \\\\
 f(x,y,z) & = & x + y + z
\\end{array}',
				'f(x) \\,\\!',
				'= \\sum_{n=0}^\\infty a_n x^n ',
				'= a_0 +a_1x+a_2x^2+\\cdots',
				'\\begin{cases} 3x + 5y + z \\\\ 7x - 2y + 4z \\\\ -6x + 3y + 2z \\end{cases}',
				'
\\begin{array}{|c|c||c|} a & b & S \\\\
\\hline
0&0&1\\\\
0&1&1\\\\
1&0&1\\\\
1&1&0\\\\
\\end{array}
',
				'( \\frac{1}{2} )',
				'\\left ( \\frac{1}{2} \\right )',
				'\\left ( \\frac{a}{b} \\right )',
				'\\left [ \\frac{a}{b} \\right ] \\quad \\left \\lbrack \\frac{a}{b} \\right \\rbrack',
				'\\left \\{ \\frac{a}{b} \\right \\} \\quad \\left \\lbrace \\frac{a}{b} \\right \\rbrace',
				'\\left \\langle \\frac{a}{b} \\right \\rangle',
				'\\left | \\frac{a}{b} \\right \\vert \\left \\Vert \\frac{c}{d} \\right \\|',
				'\\left \\lfloor \\frac{a}{b} \\right \\rfloor \\left \\lceil \\frac{c}{d} \\right \\rceil',
				'\\left / \\frac{a}{b} \\right \\backslash',
				'\\left \\uparrow \\frac{a}{b} \\right \\downarrow \\quad \\left \\Uparrow \\frac{a}{b} \\right \\Downarrow \\quad \\left \\updownarrow \\frac{a}{b} \\right \\Updownarrow',
				'\\left [ 0,1 \\right )',
				'\\left \\langle \\psi \\right |',
				'\\left . \\frac{A}{B} \\right \\} \\to X',
				'\\big( \\Big( \\bigg( \\Bigg( \\dots \\Bigg] \\bigg] \\Big] \\big]',
				'\\big\\{ \\Big\\{ \\bigg\\{ \\Bigg\\{ \\dots \\Bigg\\rangle \\bigg\\rangle \\Big\\rangle \\big\\rangle',
				'\\big\\| \\Big\\| \\bigg\\| \\Bigg\\| \\dots \\Bigg| \\bigg| \\Big| \\big|',
				'\\big\\lfloor \\Big\\lfloor \\bigg\\lfloor \\Bigg\\lfloor \\dots \\Bigg\\rceil \\bigg\\rceil \\Big\\rceil \\big\\rceil',
				'\\big\\uparrow \\Big\\uparrow \\bigg\\uparrow \\Bigg\\uparrow \\dots \\Bigg\\Downarrow \\bigg\\Downarrow \\Big\\Downarrow \\big\\Downarrow',
				'\\big\\updownarrow \\Big\\updownarrow \\bigg\\updownarrow \\Bigg\\updownarrow \\dots \\Bigg\\Updownarrow \\bigg\\Updownarrow \\Big\\Updownarrow \\big\\Updownarrow',
				'\\big / \\Big / \\bigg / \\Bigg / \\dots \\Bigg\\backslash \\bigg\\backslash \\Big\\backslash \\big\\backslash',
				'\\Alpha \\Beta \\Gamma \\Delta \\Epsilon \\Zeta \\!',
				'\\Eta \\Theta \\Iota \\Kappa \\Lambda \\Mu \\!',
				'\\Nu \\Xi \\Pi \\Rho \\Sigma \\Tau \\!',
				'\\Upsilon \\Phi \\Chi \\Psi \\Omega \\!',
				'\\varepsilon \\digamma \\varkappa \\varpi \\!',
				'\\varrho \\varsigma \\vartheta \\varphi \\!',
				'\\aleph \\beth \\gimel \\daleth \\!',
				'\\mathbb{A} \\mathbb{B} \\mathbb{C} \\mathbb{D} \\mathbb{E} \\mathbb{F} \\mathbb{G} \\!',
				'\\mathbb{H} \\mathbb{I} \\mathbb{J} \\mathbb{K} \\mathbb{L} \\mathbb{M} \\!',
				'\\mathbb{N} \\mathbb{O} \\mathbb{P} \\mathbb{Q} \\mathbb{R} \\mathbb{S} \\mathbb{T} \\!',
				'\\mathbb{U} \\mathbb{V} \\mathbb{W} \\mathbb{X} \\mathbb{Y} \\mathbb{Z} \\!',
				'\\mathbf{A} \\mathbf{B} \\mathbf{C} \\mathbf{D} \\mathbf{E} \\mathbf{F} \\mathbf{G} \\!',
				'\\mathbf{H} \\mathbf{I} \\mathbf{J} \\mathbf{K} \\mathbf{L} \\mathbf{M} \\!',
				'\\mathbf{N} \\mathbf{O} \\mathbf{P} \\mathbf{Q} \\mathbf{R} \\mathbf{S} \\mathbf{T} \\!',
				'\\mathbf{U} \\mathbf{V} \\mathbf{W} \\mathbf{X} \\mathbf{Y} \\mathbf{Z} \\!',
				'\\mathbf{0} \\mathbf{1} \\mathbf{2} \\mathbf{3} \\mathbf{4} \\!',
				'\\mathbf{5} \\mathbf{6} \\mathbf{7} \\mathbf{8} \\mathbf{9} \\!',
				'\\boldsymbol{\\Alpha} \\boldsymbol{\\Beta} \\boldsymbol{\\Gamma} \\boldsymbol{\\Delta} \\boldsymbol{\\Epsilon} \\boldsymbol{\\Zeta} \\!',
				'\\boldsymbol{\\Eta} \\boldsymbol{\\Theta} \\boldsymbol{\\Iota} \\boldsymbol{\\Kappa} \\boldsymbol{\\Lambda} \\boldsymbol{\\Mu} \\!',
				'\\boldsymbol{\\Nu} \\boldsymbol{\\Xi} \\boldsymbol{\\Pi} \\boldsymbol{\\Rho} \\boldsymbol{\\Sigma} \\boldsymbol{\\Tau} \\!',
				'\\boldsymbol{\\Upsilon} \\boldsymbol{\\Phi} \\boldsymbol{\\Chi} \\boldsymbol{\\Psi} \\boldsymbol{\\Omega} \\!',
				'\\boldsymbol{\\varepsilon} \\boldsymbol{\\digamma} \\boldsymbol{\\varkappa} \\boldsymbol{\\varpi} \\!',
				'\\boldsymbol{\\varrho} \\boldsymbol{\\varsigma} \\boldsymbol{\\vartheta} \\boldsymbol{\\varphi} \\!',
				'\\mathit{0} \\mathit{1} \\mathit{2} \\mathit{3} \\mathit{4} \\!',
				'\\mathit{5} \\mathit{6} \\mathit{7} \\mathit{8} \\mathit{9} \\!',
				'\\mathit{\\Alpha} \\mathit{\\Beta} \\mathit{\\Gamma} \\mathit{\\Delta} \\mathit{\\Epsilon} \\mathit{\\Zeta} \\!',
				'\\mathit{\\Eta} \\mathit{\\Theta} \\mathit{\\Iota} \\mathit{\\Kappa} \\mathit{\\Lambda} \\mathit{\\Mu} \\!',
				'\\mathit{\\Nu} \\mathit{\\Xi} \\mathit{\\Pi} \\mathit{\\Rho} \\mathit{\\Sigma} \\mathit{\\Tau} \\!',
				'\\mathit{\\Upsilon} \\mathit{\\Phi} \\mathit{\\Chi} \\mathit{\\Psi} \\mathit{\\Omega} \\!',
				'\\mathrm{A} \\mathrm{B} \\mathrm{C} \\mathrm{D} \\mathrm{E} \\mathrm{F} \\mathrm{G} \\!',
				'\\mathrm{H} \\mathrm{I} \\mathrm{J} \\mathrm{K} \\mathrm{L} \\mathrm{M} \\!',
				'\\mathrm{N} \\mathrm{O} \\mathrm{P} \\mathrm{Q} \\mathrm{R} \\mathrm{S} \\mathrm{T} \\!',
				'\\mathrm{U} \\mathrm{V} \\mathrm{W} \\mathrm{X} \\mathrm{Y} \\mathrm{Z} \\!',
				'\\mathrm{0} \\mathrm{1} \\mathrm{2} \\mathrm{3} \\mathrm{4} \\!',
				'\\mathrm{5} \\mathrm{6} \\mathrm{7} \\mathrm{8} \\mathrm{9} \\!',
				'\\mathsf{A} \\mathsf{B} \\mathsf{C} \\mathsf{D} \\mathsf{E} \\mathsf{F} \\mathsf{G} \\!',
				'\\mathsf{H} \\mathsf{I} \\mathsf{J} \\mathsf{K} \\mathsf{L} \\mathsf{M} \\!',
				'\\mathsf{N} \\mathsf{O} \\mathsf{P} \\mathsf{Q} \\mathsf{R} \\mathsf{S} \\mathsf{T} \\!',
				'\\mathsf{U} \\mathsf{V} \\mathsf{W} \\mathsf{X} \\mathsf{Y} \\mathsf{Z} \\!',
				'\\mathsf{0} \\mathsf{1} \\mathsf{2} \\mathsf{3} \\mathsf{4} \\!',
				'\\mathsf{5} \\mathsf{6} \\mathsf{7} \\mathsf{8} \\mathsf{9} \\!',
				'\\mathcal{A} \\mathcal{B} \\mathcal{C} \\mathcal{D} \\mathcal{E} \\mathcal{F} \\mathcal{G} \\!',
				'\\mathcal{H} \\mathcal{I} \\mathcal{J} \\mathcal{K} \\mathcal{L} \\mathcal{M} \\!',
				'\\mathcal{N} \\mathcal{O} \\mathcal{P} \\mathcal{Q} \\mathcal{R} \\mathcal{S} \\mathcal{T} \\!',
				'\\mathcal{U} \\mathcal{V} \\mathcal{W} \\mathcal{X} \\mathcal{Y} \\mathcal{Z} \\!',
				'\\mathfrak{A} \\mathfrak{B} \\mathfrak{C} \\mathfrak{D} \\mathfrak{E} \\mathfrak{F} \\mathfrak{G} \\!',
				'\\mathfrak{H} \\mathfrak{I} \\mathfrak{J} \\mathfrak{K} \\mathfrak{L} \\mathfrak{M} \\!',
				'\\mathfrak{N} \\mathfrak{O} \\mathfrak{P} \\mathfrak{Q} \\mathfrak{R} \\mathfrak{S} \\mathfrak{T} \\!',
				'\\mathfrak{U} \\mathfrak{V} \\mathfrak{W} \\mathfrak{X} \\mathfrak{Y} \\mathfrak{Z} \\!',
				'\\mathfrak{0} \\mathfrak{1} \\mathfrak{2} \\mathfrak{3} \\mathfrak{4} \\!',
				'\\mathfrak{5} \\mathfrak{6} \\mathfrak{7} \\mathfrak{8} \\mathfrak{9} \\!',
				'\\text{xyz}',
				'\\text{xyz}\\!',
				'\\text{if} n \\text{is even} ',
				'\\text{if} n \\text{is even} \\!',
				'\\text{if }n\\text{ is even}',
				'\\text{if }n\\text{ is even}\\!',
				'\\text{if}~n\\ \\text{is even} ',
				'\\text{if}~n\\ \\text{is even} \\!',
				'{\\color{Blue}x^2}+{\\color{YellowOrange}2x}-{\\color{OliveGreen}1}',
				'x_{1,2}=\\frac{-b\\pm\\sqrt{\\color{Red}b^2-4ac}}{2a}',
				'\\definecolor{orange}{RGB}{255,165,0}{\\color{orange}e^{i \\pi} + 1 = 0}',
				'\\definecolor{orange}{RGB}{255,165,0}\\pagecolor{orange}e^{i \\pi} + 1 = 0\\,\\!',
				'\\color{Apricot}\\text{Apricot}',
				'\\color{Aquamarine}\\text{Aquamarine}',
				'\\color{Bittersweet}\\text{Bittersweet}',
				'\\color{Black}\\text{Black}',
				'\\color{Blue}\\text{Blue}',
				'\\color{BlueGreen}\\text{BlueGreen}',
				'\\color{BlueViolet}\\text{BlueViolet}',
				'\\color{BrickRed}\\text{BrickRed}',
				'\\color{Brown}\\text{Brown}',
				'\\color{BurntOrange}\\text{BurntOrange}',
				'\\color{CadetBlue}\\text{CadetBlue}',
				'\\color{CarnationPink}\\text{CarnationPink}',
				'\\color{Cerulean}\\text{Cerulean}',
				'\\color{CornflowerBlue}\\text{CornflowerBlue}',
				'\\color{Cyan}\\text{Cyan}',
				'\\color{Dandelion}\\text{Dandelion}',
				'\\color{DarkOrchid}\\text{DarkOrchid}',
				'\\color{Emerald}\\text{Emerald}',
				'\\color{ForestGreen}\\text{ForestGreen}',
				'\\color{Fuchsia}\\text{Fuchsia}',
				'\\color{Goldenrod}\\text{Goldenrod}',
				'\\color{Gray}\\text{Gray}',
				'\\color{Green}\\text{Green}',
				'\\color{GreenYellow}\\text{GreenYellow}',
				'\\color{JungleGreen}\\text{JungleGreen}',
				'\\color{Lavender}\\text{Lavender}',
				'\\color{LimeGreen}\\text{LimeGreen}',
				'\\color{Magenta}\\text{Magenta}',
				'\\color{Mahogany}\\text{Mahogany}',
				'\\color{Maroon}\\text{Maroon}',
				'\\color{Melon}\\text{Melon}',
				'\\color{MidnightBlue}\\text{MidnightBlue}',
				'\\color{Mulberry}\\text{Mulberry}',
				'\\color{NavyBlue}\\text{NavyBlue}',
				'\\color{OliveGreen}\\text{OliveGreen}',
				'\\color{Orange}\\text{Orange}',
				'\\color{OrangeRed}\\text{OrangeRed}',
				'\\color{Orchid}\\text{Orchid}',
				'\\color{Peach}\\text{Peach}',
				'\\color{Periwinkle}\\text{Periwinkle}',
				'\\color{PineGreen}\\text{PineGreen}',
				'\\color{Plum}\\text{Plum}',
				'\\color{ProcessBlue}\\text{ProcessBlue}',
				'\\color{Purple}\\text{Purple}',
				'\\color{RawSienna}\\text{RawSienna}',
				'\\color{Red}\\text{Red}',
				'\\color{RedOrange}\\text{RedOrange}',
				'\\color{RedViolet}\\text{RedViolet}',
				'\\color{Rhodamine}\\text{Rhodamine}',
				'\\color{RoyalBlue}\\text{RoyalBlue}',
				'\\color{RoyalPurple}\\text{RoyalPurple}',
				'\\color{RubineRed}\\text{RubineRed}',
				'\\color{Salmon}\\text{Salmon}',
				'\\color{SeaGreen}\\text{SeaGreen}',
				'\\color{Sepia}\\text{Sepia}',
				'\\color{SkyBlue}\\text{SkyBlue}',
				'\\color{SpringGreen}\\text{SpringGreen}',
				'\\color{Tan}\\text{Tan}',
				'\\color{TealBlue}\\text{TealBlue}',
				'\\color{Thistle}\\text{Thistle}',
				'\\color{Turquoise}\\text{Turquoise}',
				'\\color{Violet}\\text{Violet}',
				'\\color{VioletRed}\\text{VioletRed}',
				'\\pagecolor{Black}\\color{White}\\text{White}',
				'\\color{WildStrawberry}\\text{WildStrawberry}',
				'\\pagecolor{Black}\\color{Yellow}\\text{Yellow}',
				'\\color{YellowGreen}\\text{YellowGreen}',
				'\\color{YellowOrange}\\text{YellowOrange}',
				'a \\qquad b',
				'a \\quad b',
				'a\\ b',
				'a \\mbox{ } b',
				'a\\;b',
				'a\\,b',
				'ab\\,',
				'a\\!b',
				'0+1+2+3+4+5+6+7+8+9+10+11+12+13+14+15+16+17+18+19+20+\\cdots',
				'{0+1+2+3+4+5+6+7+8+9+10+11+12+13+14+15+16+17+18+19+20+\\cdots}',
				'\\int_{-N}^{N} e^x\\, dx',
				'a^{\\,\\!c+2}',
				'a^{c+2} \\,',
				'a^{b^{c+2}}',
				'a^{b^{c+2}} \\,',
				'a^{b^{c+2}}\\approx 5',
				'\\approx',
				'a^{b^{\\,\\!c+2}}',
				'\\iint\\limits_{S}\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\subset\\!\\supset \\mathbf D \\cdot \\mathrm{d}\\mathbf A',
				'\\int\\!\\!\\!\\!\\int_{\\partial V}\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\;\\;\\;\\bigcirc\\,\\,\\mathbf D\\cdot\\mathrm{d}\\mathbf A',
				'\\int\\!\\!\\!\\!\\!\\int\\!\\!\\!\\!\\!\\int_{\\partial V}\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\;\\;\\;\\subset\\!\\supset \\mathbf D\\cdot\\mathrm{d}\\mathbf A',
				'\\int\\!\\!\\!\\!\\!\\int\\!\\!\\!\\!\\!\\int_{\\partial V}\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\!\\;\\;\\;\\bigcirc\\,\\,\\mathbf D\\;\\cdot\\mathrm{d}\\mathbf A',
				'\\overset{\\frown}{AB}',
				'v\\!',
				'\\nu\\!',
				'ax^2 + bx + c = 0',
				'x={-b\\pm\\sqrt{b^2-4ac} \\over 2a}',
				'2 = \\left( \\frac{\\left(3-x\\right) \\times 2}{3-x} \\right)',
				'S_{\\text{new}} = S_{\\text{old}} - \\frac{ \\left( 5-T \\right) ^2} {2}',
				'\\int_a^x \\!\\!\\!\\int_a^s f(y)\\,dy\\,ds = \\int_a^x f(y)(x-y)\\,dy',
				'\\det(\\mathsf{A}-\\lambda\\mathsf{I}) = 0',
				'\\sum_{i=0}^{n-1} i',
				'\\sum_{m=1}^\\infty\\sum_{n=1}^\\infty\\frac{m^2\\,n}{3^m\\left(m\\,3^n+n\\,3^m\\right)}',
				'u\'\' + p(x)u\' + q(x)u=f(x),\\quad x>a',
				'|\\bar{z}| = |z|, |(\\bar{z})^n| = |z|^n, \\arg(z^n) = n \\arg(z)',
				'\\lim_{z\\rightarrow z_0} f(z)=f(z_0)',
				'\\phi_n(\\kappa)
 = \\frac{1}{4\\pi^2\\kappa^2} \\int_0^\\infty \\frac{\\sin(\\kappa R)}{\\kappa R} \\frac{\\partial}{\\partial R} \\left[R^2\\frac{\\partial D_n(R)}{\\partial R}\\right]\\,dR',
				'\\phi_n(\\kappa) = 0.033C_n^2\\kappa^{-11/3},\\quad \\frac{1}{L_0}\\ll\\kappa\\ll\\frac{1}{l_0}',
				'f(x) = \\begin{cases}1 & -1 \\le x < 0 \\\\
 \\frac{1}{2} & x = 0 \\\\ 1 - x^2 & \\text{otherwise}\\end{cases}',
				'{}_pF_q(a_1,\\dots,a_p;c_1,\\dots,c_q;z) = \\sum_{n=0}^\\infty \\frac{(a_1)_n\\cdots(a_p)_n}{(c_1)_n\\cdots(c_q)_n}\\frac{z^n}{n!}',
				'\\frac{a}{b}\\ \\tfrac{a}{b}',
				'S=dD\\,\\sin\\alpha\\!',
				'V=\\frac16\\pi h\\left[3\\left(r_1^2+r_2^2\\right)+h^2\\right]',
				'\\begin{align}
  u & = \\tfrac{1}{\\sqrt{2}}(x+y) \\qquad & x &= \\tfrac{1}{\\sqrt{2}}(u+v)\\\\
  v & = \\tfrac{1}{\\sqrt{2}}(x-y) \\qquad & y &= \\tfrac{1}{\\sqrt{2}}(u-v)
 \\end{align}',
				'( \\nabla \\times \\bold{F} ) \\cdot {\\rm d}\\bold{S} = \\oint_{\\partial S} \\bold{F} \\cdot {\\rm d}\\boldsymbol{\\ell} ',
				'{\\scriptstyle S}',
				'\\oint_C \\bold{B} \\cdot {\\rm d} \\boldsymbol{\\ell} = \\mu_0 ',
				'\\left ( \\bold{J} + \\epsilon_0\\frac{\\partial \\bold{E}}{\\partial t} \\right ) \\cdot {\\rm d}\\bold{S}',
				'\\oint_{\\partial S} \\bold{B} \\cdot {\\rm d} \\boldsymbol{\\ell} = \\mu_0 ',
				'\\bold{P} = ',
				'{\\scriptstyle \\partial \\Omega}',
				'\\bold{T} \\cdot {\\rm d}^3\\boldsymbol{\\Sigma} = 0',
				'2 = \\left(
 \\frac{\\left(3-x\\right) \\times 2}{3-x}
 \\right)',
				'\\int_a^x \\!\\!\\!\\int_a^s f(y)\\,dy\\,ds
 = \\int_a^x f(y)(x-y)\\,dy',
				'\\sum_{m=1}^\\infty\\sum_{n=1}^\\infty\\frac{m^2\\,n}
 {3^m\\left(m\\,3^n+n\\,3^m\\right)}',
				'|\\bar{z}| = |z|,
 |(\\bar{z})^n| = |z|^n,
 \\arg(z^n) = n \\arg(z)',
				'\\phi_n(\\kappa) =
 \\frac{1}{4\\pi^2\\kappa^2} \\int_0^\\infty
 \\frac{\\sin(\\kappa R)}{\\kappa R}
 \\frac{\\partial}{\\partial R}
 \\left[R^2\\frac{\\partial D_n(R)}{\\partial R}\\right]\\,dR',
				'\\phi_n(\\kappa) =
 0.033C_n^2\\kappa^{-11/3},\\quad
 \\frac{1}{L_0}\\ll\\kappa\\ll\\frac{1}{l_0}',
				'
 f(x) =
 \\begin{cases}
 1 & -1 \\le x < 0 \\\\
 \\frac{1}{2} & x = 0 \\\\
 1 - x^2 & \\text{otherwise}
 \\end{cases}
 ',
				'{}_pF_q(a_1,\\dots,a_p;c_1,\\dots,c_q;z)
 = \\sum_{n=0}^\\infty
 \\frac{(a_1)_n\\cdots(a_p)_n}{(c_1)_n\\cdots(c_q)_n}
 \\frac{z^n}{n!}',
				'\\begin{align}
  u & = \\tfrac{1}{\\sqrt{2}}(x+y) \\qquad & x &= \\tfrac{1}{\\sqrt{2}}(u+v) \\\\
  v & = \\tfrac{1}{\\sqrt{2}}(x-y) \\qquad & y &= \\tfrac{1}{\\sqrt{2}}(u-v)&nbsp;&nbsp;&nbsp;
 \\end{align}',
		); }

}