/*global exports:false, module:false, require:false */

module.exports = function( grunt ) {
	'use strict';

	require('matchdep').filterDev('grunt-*').forEach( grunt.loadNpmTasks );

	grunt.initConfig({

		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			plugin: [
				'Gruntfile.js',
				'wpsc-admin/js/*.js',
				'wpsc-components/marketplace-core-v1/static/*.js',
				'wpsc-components/merchant-core-v3/gateways/*.js',
				'wpsc-components/theme-engine-v2/admin/js/*.js',
				'wpsc-components/theme-engine-v2/theming/assets/js/*.js',
				'wpsc-components/merchant-core-v3/*.js',
				'wpsc-core/js/*.js',
				'!wpsc-core/js/tinymce/*.js',
				'!wpsc-core/js/*-min.js',
				'!wpsc-core/js/jquery*.js',
				'!wpsc-admin/js/admin-legacy.js',
				'!wpsc-admin/js/jquery-*.js'
			]
		},

		makepot: {
			target: {
				options: {
					domainPath: '/wpsc-languages/',    // Where to save the POT file.
					exclude: [
								'tesst/.*',
								'bin/.*',
								'images/.*'
							],
					mainFile: 'wp-shopping-cart.php',    // Main project file.
					potFilename: 'wpsc.pot',    // Name of the POT file.
					potHeaders: {
					poedit: true,                 // Includes common Poedit headers.
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					type: 'wp-plugin',    // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true,    // Whether the POT-Creation-Date should be updated without other changes.
					processPot: function( pot, options ) {
						pot.headers['report-msgid-bugs-to'] = 'https://wpecommerce.org/';
						pot.headers['last-translator'] = 'WP-Translations (http://wp-translations.org/)';
						pot.headers['language-team'] = 'WP-Translations <wpt@wp-translations.org>';
						pot.headers['language'] = 'en_US';
						return pot;
					}
				}
			}
		},
		watch: {
			js: {
				files: ['<%= jshint.plugin %>'],
				tasks: ['jshint']
			}
		}

	});

	grunt.registerTask('default', ['jshint', 'watch', 'makepot']);

	/**
	 * PHP Code Sniffer using WordPress Coding Standards.
	 *
	 * @link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
	 */
	grunt.registerTask('phpcs', function() {
		var done = this.async();

		grunt.util.spawn({
			cmd: 'phpcs',
			args: [
				'-p',
				'-s',
				'--standard=WordPress',
				'--extensions=php',
				'--ignore=*/node_modules/*,*/tests/*',
				'--report-file=codesniffs.txt',
				'.'
			],
			opts: { stdio: 'inherit' }
		}, done);
	});

};