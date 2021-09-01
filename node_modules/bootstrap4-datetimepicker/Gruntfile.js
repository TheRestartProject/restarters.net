module.exports = function(grunt) {
  'use strict';
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    uglify: {
      target: {
        files: {
          'build/js/bootstrap-datetimepicker.min.js': 'src/js/bootstrap-datetimepicker.js'
        }
      },
      options: {
        mangle: true,
        compress: {
          dead_code: false // jshint ignore:line
        },
        output: {
          ascii_only: true // jshint ignore:line
        },
        report: 'min',
        preserveComments: 'some'
      }
    },
    jshint: {
      all: [
        'Gruntfile.js', 'src/js/*.js', 'test/*.js'
      ],
      options: {
        'browser': true,
        'node': true,
        'jquery': true,
        'boss': false,
        'curly': true,
        'debug': false,
        'devel': false,
        'eqeqeq': true,
        'bitwise': true,
        'eqnull': true,
        'evil': false,
        'forin': true,
        'immed': false,
        'laxbreak': false,
        'newcap': true,
        'noarg': true,
        'noempty': false,
        'nonew': false,
        'onevar': true,
        'plusplus': false,
        'regexp': false,
        'undef': true,
        'sub': true,
        'strict': true,
        'unused': true,
        'white': true,
        'es3': true,
        'camelcase': true,
        'quotmark': 'single',
        'globals': {
          'define': false,
          'moment': false,
          // Jasmine
          'jasmine': false,
          'describe': false,
          'xdescribe': false,
          'expect': false,
          'it': false,
          'xit': false,
          'spyOn': false,
          'beforeEach': false,
          'afterEach': false
        }
      }
    },
    jscs: {
      all: [
        'Gruntfile.js', 'src/js/*.js', 'test/*.js'
      ],
      options: {
        config: '.jscs.json'
      }
    },
    sass: {
      dev: {
        options: {
          style: 'expanded',
          sourcemap: 'none'
        },
        files: {
          './build/css/bootstrap-datetimepicker.css': './src/sass/bootstrap-datetimepicker-build.scss'
        }
      },
      dist: {
        options: {
          style: 'compressed',
          sourcemap: 'none'
        },
        files: {
          './build/css/bootstrap-datetimepicker.min.css': './src/sass/bootstrap-datetimepicker-build.scss'
        }
      }
    },
    env: {
      paris: {
        TZ: 'Europe/Paris' // sets env for phantomJS https://github.com/ariya/phantomjs/issues/10379#issuecomment-36058589
      }
    },
    connect: {
      server: {
        options: {
          port: 8099
        }
      }
    },
    jasmine: {
      customTemplate: {
        src: 'src/js/*.js',
        options: {
          specs: 'test/*Spec.js',
          helpers: 'test/*Helper.js',
          host: 'http://127.0.0.1:8099',
          styles: [
            'node_modules/bootstrap/dist/css/bootstrap.min.css',
            'build/css/bootstrap-datetimepicker.min.css'
          ],
          vendor: [
            'node_modules/jquery/dist/jquery.min.js',
            'node_modules/moment/min/moment-with-locales.min.js',
            'node_modules/moment-timezone/moment-timezone.js',
            'node_modules/bootstrap/dist/js/bootstrap.min.js'
          ],
          display: 'none',
          summary: 'true'
        }
      }
    }
  });

  grunt.loadTasks('tasks');

  grunt.loadNpmTasks('grunt-env');
  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-contrib-jasmine');
  grunt.loadNpmTasks('grunt-contrib-sass');
  grunt.loadNpmTasks('grunt-nuget');

  require('load-grunt-tasks')(grunt);
  grunt.registerTask('default', ['uglify','sass', 'env:paris', 'connect', 'jasmine']);
  grunt.registerTask('build:travis', [
    // code style
    'jshint', 'jscs',
    // build
    'uglify', 'sass',
    // tests
    'env:paris', 'connect', 'jasmine'
  ]);

  // Task to be run when building
  grunt.registerTask('build', ['jshint', 'jscs', 'uglify', 'sass']);

  grunt.registerTask('test', ['sass']);

  grunt.registerTask('docs', 'Generate docs', function() {
    grunt.util.spawn({
      cmd: 'mkdocs',
      args: ['build', '--clean']
    });
  });

  grunt.registerTask('release', function(version) {
    console.log(version);
    version = '5.2.3';
    if (!version || version.split('.').length !== 3) {
      grunt.fail.fatal('malformed version. Use grunt release:1.2.3');
    }

    grunt.task.run([
      'bump_version:' + version,
      'build:travis',
      'docs',
      'nugetpack'
    ]);
  });
};
