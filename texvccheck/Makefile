.PHONY: clean all
PREFIX = /usr/local
DESTDIR = /usr
SOURCEDIR = $(PWD)
INSTALL = /usr/bin/install
OBJ= tex.cmo texutil.cmo parser.cmo lexer.cmo texvccheck.cmo \
tex.cmx texutil.cmx parser.cmx lexer.cmx texvccheck.cmx \
lexer.cmi parser.cmi tex.cmi texutil.cmi texvccheck.cmi \
lexer.o parser.o tex.o texutil.o texvccheck.o \
lexer.ml parser.ml parser.mli texvccheck texvccheck.bc util.o \
util.cmo util.cmx util.cmi \

COMMON_NATIVE_OBJ  =util.cmx parser.cmx texutil.cmx lexer.cmx
COMMON_BYTECODE_OBJ=util.cmo parser.cmo texutil.cmo lexer.cmo

all: texvccheck
clean:
	rm -f $(OBJ)

# Native versions
texvccheck: $(COMMON_NATIVE_OBJ) texvccheck.cmx
	ocamlopt -o $@ unix.cmxa $^

# Bytecode version
texvccheck.bc: $(COMMON_BYTECODE_OBJ) texvccheck.cmo
	ocamlc -o $@ unix.cma $^

install: texvccheck
	$(INSTALL) -dm777 $(DESTDIR)/bin
	$(INSTALL) -m777 texvccheck $(DESTDIR)/bin

remove:
	rm -f $(DESTDIR)/bin/texvccheck

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
texvccheck.cmo: lexer.cmo parser.cmi texutil.cmi util.cmo
texvccheck.cmx: lexer.cmx parser.cmx texutil.cmx util.cmx
