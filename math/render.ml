(* vim: set sw=8 ts=8 et: *)

let cmd_dvips tmpprefix = "pdf2svg" ^ tmpprefix ^ ".pdf " ^ tmpprefix ^ ".svg"
let cmd_latex tmpprefix = "pdflatex " ^ tmpprefix ^ ".tex >/dev/null && pdfcrop " ^ tmpprefix ^ ".pdf " ^ tmpprefix ^ ".pdf >/dev/null"

(* Putting -transparent white in converts arguments will sort-of give you transperancy *)
let cmd_convert tmpprefix finalpath = "convert -quality 100 -density 120 " ^ tmpprefix ^ ".ps " ^ finalpath ^ " >/dev/null 2>/dev/null"

(* Putting -bg Transparent in dvipng's arguments will give full-alpha transparency *)
(* Note that IE have problems with such PNGs and need an additional javascript snippet *)
(* Putting -bg transparent in dvipng's arguments will give binary transparency *)
let cmd_pdf2svg tmpprefix finalpath backcolor = "pdf2svg " ^ tmpprefix ^ ".pdf " ^ finalpath ^ " >/dev/null 2>/dev/null"

exception ExternalCommandFailure of string

let render tmppath finalpath outtex md5 backcolor =
    let tmpprefix0 = (string_of_int (Unix.getpid ()))^"_"^md5 in
    let tmpprefix = (tmppath^"/"^tmpprefix0) in
    let unlink_all () =
      begin
        (* Commenting this block out will aid in debugging *)
        Sys.remove (tmpprefix ^ ".pdf");
        Sys.remove (tmpprefix ^ ".aux");
        Sys.remove (tmpprefix ^ ".log");
        Sys.rename (tmpprefix ^ ".tex") (tmppath^"/"^md5^".tex");
        if Sys.file_exists (tmpprefix ^ ".ps")
        then Sys.remove (tmpprefix ^ ".ps");
      end in

    let f = (Util.open_out_unless_exists (tmpprefix ^ ".tex")) in
      begin
        (* Assemble final output in file 'f' *)
        output_string f (Texutil.get_preface ());
        output_string f outtex;
        output_string f (Texutil.get_footer ());
        close_out f;

        (* TODO: document *)
        if Util.run_in_other_directory tmppath (cmd_latex tmpprefix0) != 0
          then (
            unlink_all (); raise (ExternalCommandFailure "latex")
        ) else if (Sys.command (cmd_pdf2svg tmpprefix (finalpath^"/"^md5^".svg") backcolor) != 0)
          then (
            if (Sys.command (cmd_dvips tmpprefix) != 0)
              then (
                unlink_all ();
                raise (ExternalCommandFailure "dvips")
            ) else if (Sys.command (cmd_convert tmpprefix (finalpath^"/"^md5^".svg")) != 0)
              then (
                unlink_all ();
                raise (ExternalCommandFailure "convert")
            ) else (
                unlink_all ()
            )
        ) else (
            unlink_all ()
        );
      unlink_all ()
      end
