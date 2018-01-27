module.exports = function( grunt ) {
    grunt.initConfig( { 
        addtextdomain: {
            target: {
                files: {
                    src: [
                        '*.php',
                        '**/*.php',
                        '!node_modules/**',
                        '!includes/vendor/**'
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
                    exclude: [ 'includes/vendor/.*' ]
                }
            }
        },
        uglify: {
            target: {
                files: [ {
                    expand: true,
                    cwd: 'assets/js',
                    src: [ '*.js', '!*.min.js' ],
                    dest: 'assets/js',
                    rename: function ( dst, src ) {
                        return dst + '/' + src.replace( '.js', '.min.js' );
                    }
                } ]
            }
        }
    } );

    grunt.loadNpmTasks( 'grunt-wp-i18n' );
    grunt.loadNpmTasks( 'grunt-contrib-uglify' );

    grunt.registerTask( 'i18n', [ 'addtextdomain', 'makepot' ] );
    grunt.registerTask( 'default', [ 'i18n', 'uglify' ] );
};