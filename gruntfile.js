module.exports = function (grunt) {
    grunt.initConfig({
        uglify: {
            options: {
                mangle: false
            },
            compress_lib: {
                files: {
                    'js/lib.min.js': [
                        'js/lib/signature_pad.js',
                        'js/lib/dropzone.js',
                        'js/lib/lodash.js',
                        'js/lib/bootbox.js',
                        'js/lib/toastr.js',
                        'js/lib/bootstrap.js',
                        'js/lib/selectize.js',
                        'js/lib/angular.js',
                        'js/lib/angular-animate.js',
                        'js/lib/angular-route.js',
                        'js/lib/angular-sanitize.js',
                        'js/lib/angular-selectize.js',
                        'js/lib/ng-sortable.js',
                        'js/lib/angular-google-maps.js',
                        'js/lib/angular-google-maps_dev_mapped.js',
                        'js/lib/ui-bootstrap-tpls-0.14.3.js',
                        'js/lib/angular-messages.js',
                        'js/lib/ngBootbox.js',
                        'js/lib/angular-dropzone.js',
                        'js/lib/angular-timeago.js',
                        'js/lib/angular-ui-tree.js',
                        'js/lib/angular-ui-mask.js'
                    ]
                }
            },
            compress_app: {
                files: {
                    'js/app.min.js': [
                        'js/app/main.js',
                        'js/app/routes.js',
                        'js/app/directives/*.js',
                        'js/app/services/*.js',
                        'js/app/filters/*.js',
                        'js/app/company_info/*.js',
                        'js/app/customer/*.js',
                        'js/app/employee/*.js',
                        'js/app/estimate/*.js',
                        'js/app/crew_route/*.js',
                        'js/app/product_service/*.js',
                        'js/app/quickbooks_sync/*.js',
                        'js/app/job_request/*.js',
                        'js/app/estimate_route/*.js',
                        'js/app/settings/*.js',
                    ]
                }
            }
        },
        ngtemplates: {
          app: {
            cwd: 'js',
            src: 'templates/**/*.html',
            dest: 'js/templates.min.js',
            options: {
                module: 'Erp'
            }
          }
        }
    });

    // load plugins
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-angular-templates');

    // Minify JS, CSS, angular templates
    grunt.registerTask('default', [
        'uglify:compress_lib',
        'uglify:compress_app',
        'ngtemplates'
    ]);
};
