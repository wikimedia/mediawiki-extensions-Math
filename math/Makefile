.PHONY: clean all

OBJ= tex.cmo texutil.cmo parser.cmo lexer.cmo texvc.cmo \
tex.cmx texutil.cmx parser.cmx lexer.cmx texvc.cmx \
lexer.cmi parser.cmi tex.cmi texutil.cmi texvc.cmi \
lexer.o parser.o tex.o texutil.o texvc.o \
lexer.ml parser.ml parser.mli texvc texvc.bc util.o \
util.cmo util.cmx util.cmi

COMMON_NATIVE_OBJ  =util.cmx parser.cmx texutil.cmx lexer.cmx
COMMON_BYTECODE_OBJ=util.cmo parser.cmo texutil.cmo lexer.cmo

all: texvc
clean:
	rm -f $(OBJ)

# Native versions
texvc: $(COMMON_NATIVE_OBJ) texvc.cmx
	ocamlopt -o $@ unix.cmxa $^

# Bytecode version
texvc.bc: $(COMMON_BYTECODE_OBJ) texvc.cmo
	ocamlc -o $@ unix.cma $^

#
# Pattern rules
#

#  .ml source  .mli interface
#  .cmi compiled interface
#  .cmo object       .cma library object
#  .cmx object file  .cmxa library object file
%.ml: %.mll
	ocamllex $<
%.mli %.ml: %.mly
	ocamlyacc $<
%.cmo: %.ml
	ocamlc -c $<
%.cmx: %.ml
	ocamlopt -c $<
%.cmi: %.mli
	ocamlc -c $<

# Various dependencies

lexer.cmo: parser.cmi tex.cmi texutil.cmi 
lexer.cmx: parser.cmx tex.cmi texutil.cmx 
parser.cmo: tex.cmi parser.cmi 
parser.cmx: tex.cmi parser.cmi 
parser.cmi: tex.cmi 
texutil.cmo: parser.cmi tex.cmi util.cmo texutil.cmi 
texutil.cmx: parser.cmx tex.cmi util.cmx texutil.cmi 
texutil.cmi: parser.cmi tex.cmi 
texvc.cmo: lexer.cmo parser.cmi texutil.cmi util.cmo 
texvc.cmx: lexer.cmx parser.cmx texutil.cmx util.cmx 
