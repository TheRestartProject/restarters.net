module.exports = function (grunt) {
    
    "use strict";
    
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        concat: {
            options: {
                // define a string to put between each file in the concatenated output
                separator: grunt.util.linefeed + ';' + grunt.util.linefeed,
                stripBanners: true
            },
            dist: {
                // the files to concatenate
                src: ['public/src/js/*.js'],                      /*'components/imagesloaded/imagesloaded.js',*/
                // the location of the resulting JS file
                dest: 'public/dist/js/<%= pkg.name %>.js'
            }
        },
        less: {
            always: {
                options: {
                    paths: ['css/less'],
                    plugins: [
                        new (require('less-plugin-clean-css'))({advanced: true})
                    ],
                },
                files: {
                    'public/dist/css/main.css': 'public/src/less/main.less'
                  
                }
            }
        },
        uglify: {
            js: {
                options: { 
                    preserveComments: false
                },
                files: {
                    'public/dist/js/script.min.js': ['public/dist/js/<%= pkg.name %>.js']
                }
            }
        },
        watch: {
            concat: {
                files: ['public/src/js/*.js'],
                tasks: 'concat'
            },
            less: {
                files: 'public/src/less/*',
                tasks: 'less:always',
                options: {
                    livereload: true
                }
            },
            uglify: {
                files: 'public/dist/js/<%= pkg.name %>.js',
                tasks: 'uglify:js'
            }
        }
    });
    
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    
    grunt.registerTask('default', ['watch']);
    
    
};