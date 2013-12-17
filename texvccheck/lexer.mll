{
    open Parser
    open Tex
}
let space = [' ' '\t' '\n' '\r']
let alpha = ['a'-'z' 'A'-'Z']
let literal_id = ['a'-'z' 'A'-'Z']
let literal_mn = ['0'-'9']
let literal_uf_lt = [',' ':' ';' '?' '!' '\'']
let delimiter_uf_lt = ['(' ')' '.']
let literal_uf_op = ['+' '-' '*' '=']
let delimiter_uf_op = ['/' '|']
let boxchars  = ['0'-'9' 'a'-'z' 'A'-'Z' '+' '-' '*' ',' '=' '(' ')' ':' '/' ';' '?' '.' '!' '\'' '`' ' ' '\128'-'\255']
let aboxchars = ['0'-'9' 'a'-'z' 'A'-'Z' '+' '-' '*' ',' '=' '(' ')' ':' '/' ';' '?' '.' '!' '\'' '`' ' ']
let latex_function_names = "arccos" | "arcsin" | "arctan" | "arg" | "cos" | "cosh" | "cot" | "coth" | "csc"| "deg" | "det" | "dim" | "exp" | "gcd" | "hom" | "inf" | "ker" | "lg" | "lim" | "liminf" | "limsup" | "ln" | "log" | "max" | "min" | "Pr" | "sec" | "sin" | "sinh" | "sup" | "tan" | "tanh"
let mediawiki_function_names = "arccot" | "arcsec" | "arccsc" | "sgn" | "sen"

rule token = parse
    space +			{ token lexbuf }
  | "\\text" space * '{' aboxchars + '}'
				{  let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\text", String.sub str n (String.length str - n - 1)) }
  | "\\mbox" space * '{' aboxchars + '}'
				{ let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\mbox", String.sub str n (String.length str - n - 1)) }
  | "\\hbox" space * '{' aboxchars + '}'
				{ let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\hbox", String.sub str n (String.length str - n - 1)) }
  | "\\vbox" space * '{' aboxchars + '}'
				{ let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\vbox", String.sub str n (String.length str - n - 1)) }
  | "\\text" space * '{' boxchars + '}'
				{  let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\text", String.sub str n (String.length str - n - 1)) }
  | "\\mbox" space * '{' boxchars + '}'
				{ let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\mbox", String.sub str n (String.length str - n - 1)) }
  | "\\hbox" space * '{' boxchars + '}'
				{ let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\hbox", String.sub str n (String.length str - n - 1)) }
  | "\\vbox" space * '{' boxchars + '}'
				{ let str = Lexing.lexeme lexbuf in
				  let n = String.index str '{' + 1 in
				  BOX ("\\vbox", String.sub str n (String.length str - n - 1)) }
  | literal_id			{ let str = Lexing.lexeme lexbuf in LITERAL (TEX_ONLY str) }
  | literal_mn			{ let str = Lexing.lexeme lexbuf in LITERAL (TEX_ONLY str) }
  | literal_uf_lt		{ let str = Lexing.lexeme lexbuf in LITERAL (TEX_ONLY str) }
  | delimiter_uf_lt		{ let str = Lexing.lexeme lexbuf in DELIMITER (TEX_ONLY str) }
  | "-"				{ let str = Lexing.lexeme lexbuf in LITERAL (TEX_ONLY str)}
  | literal_uf_op		{ let str = Lexing.lexeme lexbuf in LITERAL (TEX_ONLY str) }
  | delimiter_uf_op		{ let str = Lexing.lexeme lexbuf in DELIMITER (TEX_ONLY str) }
  | "\\operatorname"            {  FUN_AR1nb "\\operatorname" }
  | "\\sqrt" space * "["	{ FUN_AR1opt "\\sqrt" }
  | "\\xleftarrow" space * "["	{  FUN_AR1opt "\\xleftarrow" }
  | "\\xrightarrow" space * "["	{  FUN_AR1opt "\\xrightarrow" }
  | "\\" (latex_function_names as name) space * "("  { LITERAL (TEX_ONLY ("\\" ^ name ^ "(")) }
  | "\\" (latex_function_names as name) space * "["  { LITERAL (TEX_ONLY ("\\" ^ name ^ "[") )}
  | "\\" (latex_function_names as name) space * "\\{"  { LITERAL (TEX_ONLY ("\\" ^ name ^ "\\{")) }
  | "\\" (latex_function_names as name) space * { LITERAL (TEX_ONLY("\\" ^ name ^ " ")) }
  | "\\" (mediawiki_function_names as name) space * "("    { ( LITERAL (TEX_ONLY ("\\operatorname{" ^ name ^ "}("))) }
  | "\\" (mediawiki_function_names as name) space * "["    { ( LITERAL (TEX_ONLY ("\\operatorname{" ^ name ^ "}[")))}
  | "\\" (mediawiki_function_names as name) space * "\\{"  { ( LITERAL (TEX_ONLY ("\\operatorname{" ^ name ^ "}\\{")))}
  | "\\" (mediawiki_function_names as name) space *        { ( LITERAL (TEX_ONLY ("\\operatorname{" ^ name ^ "} "))) }
  | "\\" alpha + 		{ Texutil.find (Lexing.lexeme lexbuf) }
  | "\\," 			{ LITERAL (TEX_ONLY "\\,") }
  | "\\ " 			{ LITERAL (TEX_ONLY "\\ ") }
  | "\\;" 			{ LITERAL (TEX_ONLY "\\;") }
  | "\\!" 			{ LITERAL (TEX_ONLY "\\!") }
  | "\\{" 			{ DELIMITER (TEX_ONLY "\\{") }
  | "\\}" 			{ DELIMITER (TEX_ONLY "\\}") }
  | "\\|" 			{ DELIMITER (TEX_ONLY "\\|") }
  | "\\_" 			{ LITERAL (TEX_ONLY "\\_") }
  | "\\#" 			{ LITERAL (TEX_ONLY "\\#") }
  | "\\%"			{ LITERAL (TEX_ONLY "\\%") }
  | "\\$"			{ LITERAL (TEX_ONLY "\\$") }
  | "\\&"			{ LITERAL (TEX_ONLY "\\&") }
  | "&"				{ NEXT_CELL }
  | "\\\\"			{ NEXT_ROW }
  | "\\begin{matrix}"		{  BEGIN__MATRIX }
  | "\\end{matrix}"		{ END__MATRIX }
  | "\\begin{pmatrix}"		{  BEGIN_PMATRIX }
  | "\\end{pmatrix}"		{ END_PMATRIX }
  | "\\begin{bmatrix}"		{  BEGIN_BMATRIX }
  | "\\end{bmatrix}"		{ END_BMATRIX }
  | "\\begin{Bmatrix}"		{  BEGIN_BBMATRIX }
  | "\\end{Bmatrix}"		{ END_BBMATRIX }
  | "\\begin{vmatrix}"		{  BEGIN_VMATRIX }
  | "\\end{vmatrix}"		{ END_VMATRIX }
  | "\\begin{Vmatrix}"		{  BEGIN_VVMATRIX }
  | "\\end{Vmatrix}"		{ END_VVMATRIX }
  | "\\begin{array}"		{  BEGIN_ARRAY }
  | "\\end{array}"  		{ END_ARRAY }
  | "\\begin{align}"		{  BEGIN_ALIGN }
  | "\\end{align}"  		{ END_ALIGN }
  | "\\begin{alignat}"		{  BEGIN_ALIGNAT }
  | "\\end{alignat}"  		{ END_ALIGNAT }
  | "\\begin{smallmatrix}"	{  BEGIN_SMALLMATRIX }
  | "\\end{smallmatrix}"  	{ END_SMALLMATRIX }
  | "\\begin{cases}"		{  BEGIN_CASES }
  | "\\end{cases}"		{ END_CASES }
  | '>'				{ LITERAL (TEX_ONLY ">") }
  | '<'				{ LITERAL (TEX_ONLY "<") }
  | '%'				{ LITERAL (TEX_ONLY "\\%") }
  | '$'				{ LITERAL (TEX_ONLY "\\$") }
  | '~'				{ LITERAL (TEX_ONLY "~") }
  | '['				{ DELIMITER (TEX_ONLY "[") }
  | ']'				{ SQ_CLOSE }
  | '{'				{ CURLY_OPEN }
  | '}'				{ CURLY_CLOSE }
  | '^'				{ SUP }
  | '_'				{ SUB }
  | eof				{ EOF }
