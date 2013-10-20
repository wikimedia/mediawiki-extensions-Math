(* vim: set sw=8 ts=8 et: *)
exception LexerException of string

(* *)
let lexer_token_safe lexbuf =
    try Lexer.token lexbuf
    with Failure s -> raise (LexerException s)

(* *)
let render tree =
    let outtex = Util.mapjoin Texutil.render_tex tree in
    begin
    print_string ("+" ^ outtex);
    end

(* TODO: document
 * Arguments:
 * 1st :
 * 2nd :
 * 3rd :
 *
 * Output one character:
 *  E : Lexer exception raised
 *  F : TeX function not recognized
 *  - : Generic/Default failure code. Might be an invalid argument,
 *  S : Parsing error
 *      output file already exist, a problem with an external
 *      command ...
 *)
let _ =
    try render (
        Parser.tex_expr lexer_token_safe (
            Lexing.from_string Sys.argv.(1))
        )
    with Parsing.Parse_error -> print_string "S"
       | LexerException _ -> print_string "E"
       | Texutil.Illegal_tex_function s -> print_string ("F" ^ s)
       | Invalid_argument _ -> print_string "-"
       | Failure _ -> print_string "-"
       | _ -> print_string "-"
