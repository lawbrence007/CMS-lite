module.exports = (grunt) ->
  grunt.initConfig

    less:
      admin:
        files:
          'temp/grunt/admin.css': [
            'vendor/selectize/dist/less/selectize.default.less'
            'vendor-client/admin/assets/less/style.less'
            'vendor-client/admin/nestable.less'
          ]
      front:
        files:
          'temp/grunt/front.css': [# FIXME: jak na soubory nových rozšíření?
            'vendor/bootstrap/less/bootstrap.less'
          ]

    cssmin:
      options:
        keepSpecialComments: 0
      admin:
        files:
          'www/css/admin.min.css': [
            'temp/grunt/admin.css'
            'vendor-client/admin/assets/css/ant-icons-content.css'
            'vendor-client/admin/assets/css/bootstrap-tagsinput.css'
            'vendor-client/admin/media/aicons/styles.css'
            'vendor-client/admin/media/aicons/flaticon.css'
          ]
      front:
        files:
          'www/css/front.min.css': [
            'temp/grunt/front.css'
            'vendor-client/front/front.css'
          ]

    uglify:
      admin:
        files:
          'www/js/admin.min.js': [# TODO: minimalizovat potřebu JS
            'vendor/jquery/dist/jquery.js'
            'vendor/bootstrap/dist/js/bootstrap.js'
            'vendor/bootstrap/js/tooltip.js'
            'vendor/nette.ajax.js/nette.ajax.js'
            'vendor/nette/forms/src/assets/netteForms.js'
            'vendor/selectize/dist/js/standalone/selectize.js'
            'vendor-client/html5shiv.min.js'
            'vendor-client/respond.min.js'
            'vendor-client/jquery.nestable.js'
            'vendor-client/main.js'
          ]
      front:
        files:
          'www/js/front.min.js': [
            'vendor/nette/forms/src/assets/netteForms.js'
            'vendor-client/main.js'
          ]

    copy:
      main:
        files: [
          expand: true
          flatten: true
          src: 'vendor-client/admin/media/aicons/fonts/*'
          dest: 'www/css/fonts/'
        ,
          expand: true
          flatten: true
          src: 'vendor/bootstrap/fonts/*'
          dest: 'www/fonts/'
        ]

  # These plugins provide necessary tasks
  grunt.loadNpmTasks 'grunt-contrib-cssmin'
  grunt.loadNpmTasks 'grunt-contrib-uglify'
  grunt.loadNpmTasks 'grunt-contrib-less'
  grunt.loadNpmTasks 'grunt-contrib-copy'

  # Tasks
  grunt.registerTask 'default', [
    'less'
    'cssmin'
    'uglify'
    'copy'
  ]
