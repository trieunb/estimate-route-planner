<?php
/**
* Plugin Name: Estimate and Route Planner Pro
* Description: Estimate and route planning
* Version: 1.2.2
* Author: SFR Software
* Author URI: http://sfr-creative.com/
*/
define('ERP_VERSION', '1.2.2');
define('ERP_PLUGIN_URL', plugin_dir_url(__FILE__));  // Http URL to plugin
define('ERP_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Physical root path of plugin
define('ROOT_MENU_SLUG', 'erpp');
define('ERPP_NAVIGATION_CLASS', 'estimate-route-planner-menu');
define('ERP_PLUGIN_NAME', 'ER Planner Pro');

require_once(ERP_PLUGIN_DIR . '/config/plugin.php');

add_action('admin_menu', 'erp_setup_admin_menu');
add_action('admin_enqueue_scripts', 'erp_enqueue_scripts');
add_action('admin_enqueue_scripts', 'erp_enqueue_stylesheets');
add_action('admin_init', 'erp_custom_menu_class');

register_activation_hook(__FILE__, 'active_plugin');
register_deactivation_hook(__FILE__, 'deactive_plugin');

function active_plugin() {
    /* Insert tables to DB */
    global $wpdb;
    $sql = file_get_contents(ERP_PLUGIN_DIR . '/db/install.sql');
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    /* Add all plugin capabilities to all roles */
    global $wp_roles;
    $pluginCaps = erp_get_capabilities();
    foreach ($wp_roles->get_names() as $roleName => $label) {
        $role = get_role($roleName);
        if ($role) {
            foreach ($pluginCaps as $cap) {
                // Skip set erpp_view_sales_estimates for admin role
                if ($roleName === 'administrator' &&
                    $cap === 'erpp_view_sales_estimates') {
                    continue;
                }
                $role->add_cap($cap);
            }
        }
    }
}

function deactive_plugin() {
    /* Remove tables from DB */
    global $wpdb;
    $sql = file_get_contents(ERP_PLUGIN_DIR . '/db/uninstall.sql');
    $wpdb->query($sql);

    /* Remove all role capabitities */
    global $wp_roles;
    $pluginCaps = erp_get_capabilities();
    foreach ($wp_roles->get_names() as $roleName => $label) {
        $role = get_role($roleName);
        if ($role) {
            foreach ($pluginCaps as $cap) {
                $role->remove_cap($cap);
            }
        }
    }
}

function erp_setup_admin_menu() {
    // Root menu
    add_menu_page(
        'ER Planner Pro Dashboard',
        'ER Planner Pro',
        'erpp_access',
        ROOT_MENU_SLUG,
        'erp_load',
        'dashicons-chart-line',
        11
    );
    // Sub menus
    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Job Request',
        'New Job Request',
        'erpp_create_job_requests',
        ROOT_MENU_SLUG . '#new-job-request',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'Job Requests',
        'Job Requests',
        'erpp_list_job_requests',
        ROOT_MENU_SLUG . '#job-requests',
        'erp_load'
    );

    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Estimate Route',
        'New Estimate Route',
        'erpp_create_estimate_routes',
        ROOT_MENU_SLUG . '#new-estimate-route',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'List Estimate Routes',
        'Estimate Routes',
        'erpp_list_estimate_routes',
        ROOT_MENU_SLUG . '#estimate-routes',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Estimate',
        'New Estimate',
        'erpp_create_estimates',
        ROOT_MENU_SLUG . '#new-estimate',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'List Estimates',
        'Estimates',
        'erpp_list_estimates',
        ROOT_MENU_SLUG . '#estimates',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Crew Route',
        'New Crew Route',
        'erpp_create_crew_routes',
        ROOT_MENU_SLUG . '#new-crew-route',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'List Crew Routes',
        'Crew Routes',
        'erpp_list_crew_routes',
        ROOT_MENU_SLUG . '#crew-routes',
        'erp_load'
    );

    add_submenu_page(
        ROOT_MENU_SLUG,
        'Settings',
        'Settings',
        'erpp_settings',
        ROOT_MENU_SLUG . '#settings',
        'erp_load'
    );
    // Remove duplicate menu same with root
    remove_submenu_page(ROOT_MENU_SLUG, ROOT_MENU_SLUG);
}

function erp_get_capabilities() {
    return include_once ERP_PLUGIN_DIR . '/config/capabilities.php';
}

function current_user_is_admin() {
    $currentUser = wp_get_current_user();
    return $currentUser && in_array('administrator', $currentUser->roles);
}

function erp_get_current_user_caps() {
    $currentUser = wp_get_current_user();
    if ($currentUser) {
        return $currentUser->allcaps;
    } else {
        return [];
    }
}

function erp_load() {
    require_once 'init.php';
     // Check required configuration for Quickbooks
    $templatePath = '';
    if (ERPConfig::isOAuthTokenValid()) {
        $templatePath = 'layout.php';
    } else {
        if (current_user_is_admin()) {
            $templatePath = 'plugin/app-config.php';
        } else {
            $templatePath = 'plugin/missing-config.php';
        }
    }
    require_once TEMPLATES_DIR . $templatePath;
}

function erp_enqueue_scripts() {
    if (is_plugin_page(ROOT_MENU_SLUG)) {
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
                'angular-signature-pad' => 'js/lib/ngSignaturePad.js',
                'angular-sortable'      => 'js/lib/ng-sortable.js',
                'angular-draggable'     => 'js/lib/ng-draggable.js',
                'angular-gmap'          => 'js/lib/angular-google-maps.js',
                'angular-gmap-dev'      => 'js/lib/angular-google-maps_dev_mapped.js',
                'angular-bootbox'       => 'js/lib/ngBootbox.js',
                'angular-dropzone'      => 'js/lib/angular-dropzone.js',
                'angular-messages'      => 'js/lib/angular-messages.js',
                'angular-ui-bootstrap'  => 'js/lib/ui-bootstrap-tpls-0.13.3.js',
                'angular-timeago'       => 'js/lib/angular-timeago.js'
            ];
            foreach ($libJS as $name => $path) {
                wp_register_script(
                    $name,
                    plugins_url($path, __FILE__),
                    [], null, true
                );
                wp_enqueue_script($name);
            }
        } else {
            wp_register_script(
                'erp-js-lib',
                plugins_url('js/lib.min.js', __FILE__),
                [], ERP_VERSION, true
            );
            wp_enqueue_script('erp-js-lib');
        }

        if (ERP_DEBUG) {
            $appJS = [
                'erp-js-app'            => 'js/app/main.js',
                'erp-js-app-routes'     => 'js/app/routes.js',
                'erp-js-app-factories'  => 'js/app/factories.js',
                'erp-js-app-directives' => 'js/app/directives.js',
                'erp-js-app-services'   => 'js/app/services.js'
            ];

            foreach ($appJS as $name => $path) {
                wp_register_script(
                    $name,
                    plugins_url($path, __FILE__),
                    [], ERP_VERSION, true
                );
                wp_enqueue_script($name);
            }

            // Scan and load all components in js/app/
            $appComponentLocations = [
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
                                    plugins_url($location . '/' . $entry, __FILE__),
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
                plugins_url('js/app.min.js', __FILE__),
                [], ERP_VERSION, true
            );
            wp_enqueue_script('erp-js-app');

            wp_register_script(
                'erp-js-templates',
                plugins_url('js/templates.min.js', __FILE__),
                [], ERP_VERSION, true
            );
            wp_enqueue_script('erp-js-templates');
        }
        // Google map API js
        wp_register_script(
            'gmap-api-js',
            'http://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false',
            [], null, false
        );
        wp_enqueue_script('gmap-api-js');

        // Register global JS variables required in app
        if (ERP_DEBUG) {
            $templatesPath = plugins_url('js/templates' . '/', __FILE__);
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

function erp_enqueue_stylesheets() {
    if (is_plugin_page(ROOT_MENU_SLUG)) {
        $libCss = [
            'bootstrap' => 'css/lib/bootstrap.min.css',
            'toastr'    => 'css/lib/toastr.css',
            'selectize' => 'css/lib/selectize.bootstrap3.css'
        ];
        foreach ($libCss as $name => $path) {
            wp_register_style(
                $name,
                plugins_url($path, __FILE__),
                false,
                null
            );
            wp_enqueue_style($name);
        }
        wp_register_style(
            'erp_main_stylesheet',
            plugins_url('css/style.css', __FILE__),
            false,
            ERP_VERSION
        );
        wp_enqueue_style('erp_main_stylesheet');
    }
}

function erp_custom_menu_class() {
    global $menu;
    if ($menu) {
        foreach($menu as $key => $value) {
            if (ROOT_MENU_SLUG == $value[2]) {
                $menu[$key][4] .= ' ' . ERPP_NAVIGATION_CLASS;
            }
        }
    }
}

add_action('wp_ajax_erp', 'erp_ajax_handler');

function erp_ajax_handler() {
    require_once ERP_PLUGIN_DIR . 'init.php';
    $app = new ERPApp();
    // Start app
    $app->letGo();
    wp_die();
}
/**
 * Add custom query in url
 */
function erp_query_vars($vars) {
    $vars[] = '_do';
    return $vars;
}

function erp_start_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_save_path(ERP_SESSION_SAVE_PATH);
        @session_start();
        if (session_status() == PHP_SESSION_NONE) {
            wp_die("Error: PHP session could not start. Please check your config.");
        }
    }
}

add_filter('query_vars', 'erp_query_vars');

// TODO: move it to another file
function erp_parse_request($wp) {
    require_once 'init.php';
    if (array_key_exists('_do', $wp->query_vars)) {
        // Handler Quickbooks authentication
        if (current_user_is_admin()) {
            $authService = new QuickbooksAuth();
            if ($wp->query_vars['_do'] == 'startQuickbooksAuthenticate') {
                // PHP Session
                erp_start_session();
                $result = $authService->start(site_url() . '?_do=verifyQuickbooksAuthenticate');
                $_SESSION['oauth_token_secret'] = null;
                if ($result['success']) {
                    $_SESSION['oauth_token_secret'] = $result['oauth_token_secret'];
                    wp_redirect($result['redirect_url']); exit;
                } else {
                    wp_die(
                        "Error while authenticating with Quickbooks: " . $result['message'] . '<br>'.
                        'Please make sure the app consumer keys are correct.'
                    );
                }
            } elseif ($wp->query_vars['_do'] == 'verifyQuickbooksAuthenticate') {
                erp_start_session();
                $result = $authService->getOauthAccessToken(
                    $_GET['oauth_token'], $_SESSION['oauth_token_secret']
                );
                if ($result['success']) {
                    // Insert to perferences table
                    $data = [];
                    $data['qbo_oauth_token']      = $result['oauth_token'];
                    $data['qbo_oauth_secret']     = $result['oauth_token_secret'];
                    $data['qbo_company_id']       = $_GET['realmId'];
                    $data['qbo_token_expires_at'] = date("Y-m-d H:i:s", strtotime("+179 days"));
                    $prefs = ORM::forTable('preferences')->findOne();
                    if (!$prefs) {
                        $prefs = ORM::forTable('preferences')->create();
                    }
                    $prefs->set($data);
                    if ($prefs->save()) {
                        require_once TEMPLATES_DIR . 'plugin/quickbooks-authenticate-success.php';
                    } else {
                        wp_die("Error while saving user OAuth tokens to database!");
                    }
                } else {
                    wp_die("Error while get access token from QBO!");
                }
            }
        } else {
            wp_die("You are not authorized to access this page!");
        }
    }
}
add_action('parse_request', 'erp_parse_request');
?>
