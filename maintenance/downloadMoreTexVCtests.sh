#!/bin/bash

# Downloads files which contain input and supposed output for some tests of WikiTexVC within this extension.
#
# The tests are deactivated by default. Running this script to download the files activates the tests in
# EnWikiFormulaeTest and andChemRegressionTest.

# Both files of EnWikiFormulaeTest are from https://doi.org/10.5281/zenodo.23421825
# Downloads the file containing all english wikipedia formula to the testfolder
FILEPATH=../tests/phpunit/integration/WikiTexVC/en-wiki-formulae-good.json
URL=https://zenodo.org/records/23421825/files/en-wiki-formulae-good.json
curl $URL -o $FILEPATH

# Downloads the file containing for chem-regression tests to the testfolder
FILEPATH=../tests/phpunit/unit/WikiTexVC/chem-regression.json
URL=https://raw.githubusercontent.com/wikimedia/mediawiki-services-texvcjs/fb56991251b8889b554fc42ef9fe4825bc35d0ed/test/chem-regression.json
curl $URL -o $FILEPATH

# Downloads the file containing reference renderings for all english wikipedia chem-regression tests to the testfolder
FILEPATH=../tests/phpunit/integration/WikiTexVC/en-wiki-formulae-good-reference.json
URL=https://zenodo.org/records/23421825/files/normalized.json
curl $URL -o $FILEPATH

# Download relaxng into the test folder
FILEPATH=../src/WikiTexVC/mathml4-core.rng
URL=https://www.w3.org/Math/RelaxNG/mathml4/mathml4-core.rng
curl $URL -o $FILEPATH
