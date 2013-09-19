(* vim: set sw=8 ts=8 et: *)

let cmd_latex tmpprefix = "pdflatex " ^ tmpprefix ^ ".tex >/dev/null"
let cmd_crop tmpprefix = "pdfcrop " ^ tmpprefix ^ ".pdf " ^ tmpprefix ^ ".pdf >/dev/null"
let cmd_pdf2svg tmpprefix = "pdf2svg " ^ tmpprefix ^ ".pdf " ^ tmpprefix ^ ".svg >/dev/null"

exception ExternalCommandFailure of string

let render tmppath outtex md5 =
    let tmpprefix0 = (string_of_int (Unix.getpid ()))^"_"^md5 in
    let tmpprefix = (tmppath^"/"^tmpprefix0) in
    let unlink_all () =
      begin
        (* Commenting this block out will aid in debugging *)
        Sys.remove (tmpprefix ^ ".pdf");
        Sys.remove (tmpprefix ^ ".aux");
        Sys.remove (tmpprefix ^ ".log");
        Sys.rename (tmpprefix ^ ".tex") (tmppath^"/"^md5^".tex");
        Sys.rename (tmpprefix ^ ".svg") (tmppath^"/"^md5^".svg");
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
            raise (ExternalCommandFailure "latex")
        );
        if (Util.run_in_other_directory tmppath (cmd_crop tmpprefix) != 0)
          then (
            raise (ExternalCommandFailure "dvipng")
          );
        if (Util.run_in_other_directory tmppath (cmd_pdf2svg tmpprefix) != 0)
          then (
            raise (ExternalCommandFailure "gnese")
          );
      unlink_all ()
      end
