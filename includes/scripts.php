<?php

function erp_enqueue_scripts($hook) {
    if (strpos($hook, ROOT_MENU_SLUG) !== false) {
        if (ERP_DEBUG) {
            // Note: The order is important for make the plugins work together
            $libJS = [
                // JS plugins
                'signature-pad'         => 'js/lib/signature_pad.js',
                'dropzone'              => 'js/lib/dropzone.js',
                'lodash'                => 'js/lib/lodash.js',
                'bootbox'               => 'js/lib/bootbox.js',
                'toastr'                => 'js/lib/toastr.js',
                'bootstrap'             => 'js/lib/bootstrap.js',
                'selectize'             => 'js/lib/selectize.js',
                // Angular libraries
                'angular-core'          => 'js/lib/angular.js',
                'angular-animate'       => 'js/lib/angular-animate.js',
                'angular-route'         => 'js/lib/angular-route.js',
                'angular-sanitize'      => 'js/lib/angular-sanitize.js',
                'angular-selectize'     => 'js/lib/angular-selectize.js',
                'angular-sortable'      => 'js/lib/ng-sortable.js',
                'angular-gmap'          => 'js/lib/angular-google-maps.js',
                'angular-gmap-dev'      => 'js/lib/angular-google-maps_dev_mapped.js',
                'angular-bootbox'       => 'js/lib/ngBootbox.js',
                'angular-dropzone'      => 'js/lib/angular-dropzone.js',
                'angular-messages'      => 'js/lib/angular-messages.js',
                'angular-ui-bootstrap'  => 'js/lib/ui-bootstrap-tpls-0.14.3.js',
                'angular-timeago'       => 'js/lib/angular-timeago.js',
                'angular-ui-tree'       => 'js/lib/angular-ui-tree.js',
                'angular-ui-mask'       => 'js/lib/angular-ui-mask.js',
            ];
            foreach ($libJS as $name => $path) {
                wp_register_script(
                    $name,
                    plugins_url($path, ERP_PLUGIN_SCRIPT),
                    [], null, true
                );
                wp_enqueue_script($name);
            }
        } else {
            wp_register_script(
                'erp-js-lib',
                plugins_url('js/lib.min.js', ERP_PLUGIN_SCRIPT),
                [], ERP_VERSION, true
            );
            wp_enqueue_script('erp-js-lib');
        }

        if (ERP_DEBUG) {
            $appJS = [
                'erp-js-app'            => 'js/app/main.js',
                'erp-js-app-routes'     => 'js/app/routes.js'
            ];

            foreach ($appJS as $name => $path) {
                wp_register_script(
                    $name,
                    plugins_url($path, ERP_PLUGIN_SCRIPT),
                    [], ERP_VERSION, true
                );
                wp_enqueue_script($name);
            }

            // Scan and load all components in js/app/
            $appComponentLocations = [
                'js/app/directives',
                'js/app/services',
                'js/app/filters',
                'js/app/estimate',
                'js/app/job_request',
                'js/app/settings',
                'js/app/company_info',
                'js/app/customer',
                'js/app/employee',
                'js/app/product_service',
                'js/app/estimate_route',
                'js/app/crew_route',
                'js/app/quickbooks_sync'
            ];

            foreach ($appComponentLocations as $location) {
                if ($handle = opendir(ERP_PLUGIN_DIR . $location)) {
                    while (false !== ($entry = readdir($handle))) {
                        if ($entry <> "." && $entry <> "..") {
                            if (filetype(ERP_PLUGIN_DIR . $location . '/' . $entry) == 'file') {
                                $registerSlug = "erp-app-" . strtolower($entry);
                                wp_register_script(
                                    $registerSlug,
                                    plugins_url($location . '/' . $entry, ERP_PLUGIN_SCRIPT),
                                    [], ERP_VERSION, true
                                );
                                wp_enqueue_script($registerSlug);
                            }
                        }
                    }
                    closedir($handle);
                }
            }
        } else {
            wp_register_script(
                'erp-js-app',
                plugins_url('js/app.min.js', ERP_PLUGIN_SCRIPT),
                [], ERP_VERSION, true
            );
            wp_enqueue_script('erp-js-app');

            wp_register_script(
                'erp-js-templates',
                plugins_url('js/templates.min.js', ERP_PLUGIN_SCRIPT),
                [], ERP_VERSION, true
            );
            wp_enqueue_script('erp-js-templates');
        }
        // Google map API js
        wp_register_script(
            'gmap-api-js',
            'http://maps.googleapis.com/maps/api/js?v=3.exp',
            [], null, false
        );
        wp_enqueue_script('gmap-api-js');

        // Register global JS variables required in app
        if (ERP_DEBUG) {
            $templatesPath = plugins_url('js/templates' . '/', ERP_PLUGIN_SCRIPT);
        } else {
            // Use preload templates
            $templatesPath = 'templates/';
        }
        wp_localize_script(
            'erp-js-app',
            'ERPApp',
            [
                'baseAPIPath'       => admin_url('admin-ajax.php' . '?action=erp'),
                'templatesPath'     => $templatesPath,
                'baseERPPluginUrl'  => ERP_PLUGIN_URL,
                'navigationClass'   => ERPP_NAVIGATION_CLASS,
                'version'           => ERP_VERSION,
                'timezone'          => ERP_TIMEZONE,
                'pluginName'        => ERP_PLUGIN_NAME
            ]
        );
    }
}

?>
