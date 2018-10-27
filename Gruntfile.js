module.exports = function (grunt) {
    var pkg = require('./package.json');

    grunt.initConfig({
        addtextdomain: {
            target: {
                files: {
                    src: [
                        '*.php',
                        '**/*.php',
                        '!node_modules/**',
                        '!includes/vendor/**',
                        '!build/**'
                    ]
                }
            }
        },
        makepot: {
            target: {
                options: {
                    potHeaders: {
                        poedit: true,
                        'x-poedit-keywordslist': true,
                        'report-msgid-bugs-to': 'https://github.com/bporcelli/wc-ratesync/issues'
                    },
                    type: 'wp-plugin',
                    exclude: ['includes/vendor/.*', 'build/.*']
                }
            }
        },
        uglify: {
            target: {
                files: [{
                    expand: true,
                    cwd: 'assets/js',
                    src: ['*.js', '!*.min.js'],
                    dest: 'assets/js',
                    rename: function (dst, src) {
                        return dst + '/' + src.replace('.js', '.min.js');
                    }
                }]
            }
        },
        clean: ['build/'],
        copy: {
            target: {
                expand: true,
                src: ['assets/**', 'data/**', 'includes/**', 'languages/**', 'wc-ratesync.php'],
                dest: 'build/'
            }
        },
        compress: {
            target: {
                options: {
                    archive: function () {
                        return 'releases/wc-ratesync-' + pkg.version + '.zip'
                    }
                },
                files: [{
                    expand: true,
                    cwd: 'build/',
                    src: '**',
                    dest: 'wc-ratesync/'
                }]
            }
        }
    });

    grunt.loadNpmTasks('grunt-wp-i18n');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-compress');

    grunt.registerTask('i18n', ['addtextdomain', 'makepot']);
    grunt.registerTask('build', ['i18n', 'uglify', 'clean', 'copy', 'compress']);
    grunt.registerTask('default', ['build']);
};
