/* eslint-env node */
module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		banana: conf.MessagesDirs,
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**'
			]
		},
		stylelint: {
			core: {
				src: [
					'**/*.css',
					'!modules/ve-math/**',
					'!node_modules/**'
				]
			},
			've-math': {
				options: {
					configFile: 'modules/ve-math/.stylelintrc'
				},
				src: [
					'modules/ve-math/**/*.css'
				]
			}
		},
		watch: {
			files: [
				'.{stylelintrc,.eslintrc.json}',
				'<%= eslint.all %>',
				'<%= stylelint.core.src %>',
				'<%= stylelint[ "ve-math" ].src %>'
			],
			tasks: 'test'
		},
		eslint: {
			all: [
				'*.js',
				'modules/**/*.js',
				'!**/node_modules/**'
			]
		}
	} );

	grunt.registerTask( 'test', [ 'eslint', 'stylelint', 'jsonlint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
