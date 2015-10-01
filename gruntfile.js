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
                        'js/lib/ngSignaturePad.js',
                        'js/lib/ng-sortable.js',
                        'js/lib/ng-draggable.js',
                        'js/lib/angular-google-maps.js',
                        'js/lib/angular-google-maps_dev_mapped.js',
                        'js/lib/ui-bootstrap-tpls-0.13.3.js',
                        'js/lib/angular-messages.js',
                        'js/lib/ngBootbox.js',
                        'js/lib/angular-dropzone.js',
                        'js/lib/angular-timeago.js'
                    ]
                }
            },
            compress_app: {
                files: {
                    'js/app.min.js': [
                        'js/app/main.js',
                        'js/app/routes.js',
                        'js/app/factories.js',
                        'js/app/directives.js',
                        'js/app/services.js',
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
        }
    });

// load plugins
grunt.loadNpmTasks('grunt-contrib-uglify');

// Register at least this one task
grunt.registerTask('default', [ 'uglify:compress_lib', 'uglify:compress_app' ]);
};
