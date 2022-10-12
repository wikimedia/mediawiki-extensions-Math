#!/bin/bash
# Downloads the file containing all english wikipedia formula to the testfolder
# this is required for running the corresponding tests in EnWikiFormulaeTest.php locally.
# Mind that the tests have to be activated in the php file.
FILEPATH=../tests/phpunit/unit/TexVC/en-wiki-formulae.json
URL=https://raw.githubusercontent.com/wikimedia/mediawiki-services-texvcjs/fb56991251b8889b554fc42ef9fe4825bc35d0ed/test/en-wiki-formulae.json
curl $URL -o $FILEPATH
