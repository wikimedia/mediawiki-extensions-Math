(* vim: set sw=8 ts=8 et: *)

(* TODO document *)
let mapjoin f l = (List.fold_left (fun a b -> a ^ (f b)) "" l)

(* TODO document *)
let mapjoine e f = function
    [] -> ""
  | h::t -> (List.fold_left (fun a b -> a ^ e ^ (f b)) (f h) t)