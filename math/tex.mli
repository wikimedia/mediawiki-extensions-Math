type font_force =
    FONTFORCE_IT
  | FONTFORCE_RM

type font_class =
    FONT_IT  (* IT default, may be forced to be RM *)
  | FONT_RM  (* RM default, may be forced to be IT *)
  | FONT_UF  (* not affected by IT/RM setting *)
  | FONT_RTI (* RM - any, IT - not available in HTML *)
  | FONT_UFH (* in TeX UF, in HTML RM *)

type math_class =
    MN
  | MI
  | MO

type render_t =
      HTMLABLEC of font_class * string * string
    | HTMLABLEM of font_class * string * string
    | HTMLABLE of font_class * string * string
    | MHTMLABLEC of font_class * string * string * math_class * string
    | HTMLABLE_BIG of string * string
    | TEX_ONLY of string

type t =
      TEX_LITERAL of render_t
    | TEX_CURLY of t list
    | TEX_FQ of t * t * t
    | TEX_DQ of t * t
    | TEX_UQ of t * t
    | TEX_FQN of t * t
    | TEX_DQN of t
    | TEX_UQN of t
    | TEX_LR of render_t * render_t * t list
    | TEX_BOX of string * string
    | TEX_BIG of string * render_t
    | TEX_FUN1 of string * t
    | TEX_FUN1nb of string * t
    | TEX_FUN2 of string * t * t
    | TEX_FUN2nb of string * t * t
    | TEX_INFIX of string * t list * t list
    | TEX_FUN2sq of string * t * t
    | TEX_FUN1hl  of string * (string * string) * t
    | TEX_FUN1hf  of string * font_force * t
    | TEX_FUN2h  of string * (t -> t -> string * string * string) * t * t
    | TEX_INFIXh of string * (t list -> t list -> string * string * string) * t list * t list
    | TEX_MATRIX of string * t list list list
    | TEX_DECLh  of string * font_force * t list
