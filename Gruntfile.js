/*global module:false, require:false */

module.exports = function( grunt ) {
	'use strict';

	require('matchdep').filterDev('grunt-*').forEach( grunt.loadNpmTasks );

	var bannerTemplate = '/**\n' + ' * <%= pkg.title %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %>\n' + ' * <%= pkg.author.url %>\n' + ' *\n' + ' * Copyright (c) <%= grunt.template.today("yyyy") %>;\n' + ' * Licensed GPLv2+\n' + ' */\n';

	var compactBannerTemplate = '/** ' + '<%= pkg.title %> - v<%= pkg.version %> - <%= grunt.template.today("yyyy-mm-dd") %> | <%= pkg.author.url %> | Copyright (c) <%= grunt.template.today("yyyy") %>; | Licensed GPLv2+' + ' **/\n';

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		asciify: {
			banner: {
				text    : 'WP eCommerce',
				options : {
					font : 'speed',
					log  : true
				}
			}
		},

		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},

			all: {
				src: [
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
					'!wpsc-admin/js/jquery-*.js',
					'!wpsc-components/theme-engine-v2/admin/js/select2*.js',
					'!wpsc-components/theme-engine-v2/theming/assets/js/jquery.*.js',
					'!*.min.js'
				]
			},
			watch: {
				src : [
					'Gruntfile.js',
					'wpsc-components/theme-engine-v2/theming/assets/js/**/*.js',
					'!wpsc-components/theme-engine-v2/theming/assets/js/jquery.*.js',
					'!wpsc-components/theme-engine-v2/theming/assets/js/floatlabel.js',
					'!wpsc-components/theme-engine-v2/theming/assets/js/fluidbox.js',
					'!wpsc-components/theme-engine-v2/theming/assets/js/cart-notifications.js',
					'!**/*.min.js'
				]
			}
		},

		browserify: {
			options: {
				banner: bannerTemplate,
				stripBanners: true,
				transform: [
					'babelify',
					'browserify-shim'
				]
			},
			dist: { files: {
				'wpsc-components/theme-engine-v2/theming/assets/js/cart-notifications.js' : 'wpsc-components/theme-engine-v2/theming/assets/js/components/cart-notifications-main.js'
			} }
		},

		uglify: {
			all: {
				options: {
					banner: compactBannerTemplate,
					mangle: false
				},
				files: [{
					expand: true,
					cwd: 'wpsc-components/theme-engine-v2/theming/assets/js',
					src: ['*.js', '!*.min.js'],
					dest: 'wpsc-components/theme-engine-v2/theming/assets/js',
					extDot: 'last',
					ext: '.min.js'
				}]
			},
			noBanner : {
				options: {
					mangle: false
				},
				files: [{
					expand: true,
					cwd: 'wpsc-components/theme-engine-v2/theming/assets/js',
					src: ['jquery.*.js', '!jquery.*.min.js'],
					dest: 'wpsc-components/theme-engine-v2/theming/assets/js',
					extDot: 'last',
					ext: '.min.js'
				}]
			}
		},

		sass: {
			dist: {
				options: {
					style: 'expanded',
					lineNumbers: false
				},
				files: [{
					expand: true,
					cwd: 'wpsc-components/theme-engine-v2/theming/assets/scss',
					src: ['**/*.scss'],
					dest: 'wpsc-components/theme-engine-v2/theming/assets/css/',
					ext: '.css'
				}]
			}
		},

		cmq: {
			options: {
				log: false
			},
			dist: {
				files: [{
					expand: true,
					cwd: 'wpsc-components/theme-engine-v2/theming/assets/css',
					src: ['*.css', '!*.min.css', '!wpsc-components/theme-engine-v2/theming/assets/css/font-awesome-ie7.css'],
					dest: 'wpsc-components/theme-engine-v2/theming/assets/css/'
				}]
			}
		},

		cssmin: {
			target: {
				files: [{
					expand: true,
					cwd: 'wpsc-components/theme-engine-v2/theming/assets/css',
					src: ['*.css', '!*.min.css', '!wpsc-components/theme-engine-v2/theming/assets/css/font-awesome-ie7.css'],
					dest: 'wpsc-components/theme-engine-v2/theming/assets/css',
					ext: '.min.css'
				}]
			}
		},

		// Check textdomain errors.
		checktextdomain: {
			options:{
				text_domain: 'wp-e-commerce',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src:  [
					'**/*.php', // Include all files
					'!node_modules/**', // Exclude node_modules/
					'!tests/**', // Exclude tests/
					'!bin/**', // Exclude bin/
					'!tmp/**' // Exclude tmp/
				],
				expand: true
			}
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
					potFilename: 'wp-e-commerce.pot',    // Name of the POT file.
					potHeaders: {
					poedit: true,                 // Includes common Poedit headers.
						'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
					},
					type: 'wp-plugin',    // Type of project (wp-plugin or wp-theme).
					updateTimestamp: true,    // Whether the POT-Creation-Date should be updated without other changes.
					processPot: function( pot ) {
						pot.headers['report-msgid-bugs-to'] = 'https://wpecommerce.org/';
						pot.headers['last-translator'] = 'WP-Translations (http://wp-translations.org/)';
						pot.headers['language-team'] = 'WP-Translations <wpt@wp-translations.org>';
						pot.headers.language = 'en_US';
						return pot;
					}
				}
			}
		},

		watch: {
			css: {
				files: ['wpsc-components/theme-engine-v2/theming/assets/scss/**/*.scss'],
				tasks: ['css'],
				options: {
					spawn: false
				}
			},
			js: {
				// files: ['<%= jshint.watch.src %>'],
				files: ['**/*.js', '!**/*.min.js'],
				tasks: ['watchjs'],
				options: {
					debounceDelay: 500
				}
			}
		}

	});

	grunt.registerTask('css', ['asciify', 'sass', 'cmq', 'cssmin']);
	grunt.registerTask('js', ['asciify', 'jshint', 'browserify', 'uglify']);
	grunt.registerTask('watchjs', ['asciify', 'jshint:watch', 'browserify', 'uglify']);
	grunt.registerTask('default', ['asciify', 'js', 'css', 'makepot']);

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
