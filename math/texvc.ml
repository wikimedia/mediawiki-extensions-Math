(* vim: set sw=8 ts=8 et: *)
exception LexerException of string

(* *)
let lexer_token_safe lexbuf =
    try Lexer.token lexbuf
    with Failure s -> raise (LexerException s)

(* *)
let render tmppath tree md5 =
    let outtex = Util.mapjoin Texutil.render_tex tree in
    begin
    print_string ("+" ^ md5);
    Render.render tmppath outtex md5
    end

(* TODO: document
 * Arguments:
 * 1st :
 * 2nd :
 * 3rd :
 *
 * Output one character:
 *  S : Parsing error
 *  E : Lexer exception raised
 *  F : TeX function not recognized
 *  - : Generic/Default failure code. Might be an invalid argument,
 *      output file already exist, a problem with an external
 *      command ...
 *)
let _ =
    Texutil.set_encoding ("UTF-8");
    try render Sys.argv.(1) (
        Parser.tex_expr lexer_token_safe (
            Lexing.from_string Sys.argv.(2))
        ) (Sys.argv.(3))
    with Parsing.Parse_error -> print_string "S"
       | LexerException _ -> print_string "E"
       | Texutil.Illegal_tex_function s -> print_string ("F" ^ s)
       | Util.FileAlreadyExists -> print_string "-"
       | Invalid_argument _ -> print_string "-"
       | Failure _ -> print_string "-"
       | Render.ExternalCommandFailure s -> ()
       | _ -> print_string "-"
