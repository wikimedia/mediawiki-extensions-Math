'use strict';

const { defineConfig, globalIgnores } = require( 'eslint/config' );
const wikimedia = require( 'eslint-config-wikimedia/flat' );

// Directories with their own config, which other configs don't apply to.
// These were directories with `"root": true` in .eslintrc.json.
const rootDirs = {
	modules: [ 'modules/**' ],
	veMathTools: [ 'modules/ve-math/tools/**' ],
	apiTesting: [ 'tests/api-testing/**' ],
	qunit: [ 'tests/qunit/**' ],
	selenium: [ 'tests/selenium/**' ]
};

module.exports = defineConfig( [
	globalIgnores( [
		// Upstream code
		'modules/mathjax/',
		'modules/mathjax-newcm-font/',
		'modules/mathjax-tex-font/',

		// Build
		'vendor/',
		'docs/',

		// Very large files from .gitignore
		'**/wmf_texvc_inputs.json',
		'**/en-wiki-formulae.json',
		'**/en-wiki-formulae-good.json',
		'**/en-wiki-formulae-good-reference.json',

		// Language files written automatically by TranslateWiki
		'**/i18n/**/*.json',
		'!**/i18n/**/en.json',
		'!**/i18n/**/qqq.json'
	] ),

	// Gruntfile.js, maintenance scripts, etc.
	{
		ignores: Object.values( rootDirs ).flat(),
		extends: [ wikimedia( 'server' ) ]
	},

	// Code which runs in MediaWiki
	{
		files: rootDirs.modules,
		ignores: rootDirs.veMathTools,
		extends: [ wikimedia( 'client', 'jquery', 'mediawiki' ) ],
		rules: {
			'max-len': 'off'
		}
	},
	{
		files: [ 'modules/ve-math/**' ],
		ignores: rootDirs.veMathTools,
		languageOptions: {
			globals: {
				ve: 'readonly'
			}
		},
		rules: {
			'no-jquery/no-done-fail': 'warn'
		}
	},
	{
		files: [ 'modules/ve-math/tests/**' ],
		extends: [ wikimedia( 'qunit' ) ]
	},
	{
		// After the wikimedia configs, which set sourceType to 'script'
		files: rootDirs.modules,
		ignores: rootDirs.veMathTools,
		languageOptions: {
			sourceType: 'commonjs',
			globals: {
				global: 'readonly'
			}
		}
	},
	{
		files: rootDirs.veMathTools,
		extends: [ wikimedia( 'server' ) ],
		rules: {
			'max-len': 'off'
		}
	},

	// Tests
	{
		files: rootDirs.apiTesting,
		extends: [ wikimedia( 'server', 'node', 'language/es2017' ) ],
		languageOptions: {
			ecmaVersion: 2018,
			// Mocha globals
			globals: {
				after: 'readonly',
				afterEach: 'readonly',
				before: 'readonly',
				beforeEach: 'readonly',
				context: 'readonly',
				describe: 'readonly',
				it: 'readonly',
				mocha: 'readonly',
				run: 'readonly',
				setup: 'readonly',
				specify: 'readonly',
				suite: 'readonly',
				suiteSetup: 'readonly',
				suiteTeardown: 'readonly',
				teardown: 'readonly',
				test: 'readonly',
				xcontext: 'readonly',
				xdescribe: 'readonly',
				xit: 'readonly',
				xspecify: 'readonly'
			}
		}
	},
	{
		files: rootDirs.qunit,
		extends: [ wikimedia( 'mediawiki/qunit' ) ]
	},
	{
		files: rootDirs.selenium,
		extends: [ wikimedia( 'selenium' ) ],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: 'module'
		}
	}
] );
