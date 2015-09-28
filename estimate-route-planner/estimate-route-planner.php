<?php
/**
* Plugin Name: Estimate and Route Planner Pro
* Description: Estimate and route planning with QuickBooks Online API
* Version: 0.2.3
* Author: SFR Software
* Author URI: http://sfr-creative.com/
*/
define('ERP_VERSION', '0.2.3');
define('ERP_PLUGIN_URL', plugin_dir_url(__FILE__));  // Http URL to plugin
define('ERP_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Physical root path of plugin
define('ROOT_MENU_SLUG', 'estimate-route-planner');
define('ERP_CONFIG_PAGE_SLUG', 'estimate-route-planner-config');
define('ERPP_NAVIGATION_CLASS', 'estimate-route-planner-menu');
define('ERP_SYNC_EVENT', 'erp_sync_event');
define('ERP_ENABLE_DEBUG', true);

add_action('admin_menu', 'erp_setup_admin_menu');
add_action('admin_enqueue_scripts', 'erp_enqueue_scripts');
add_action('admin_enqueue_scripts', 'erp_enqueue_stylesheets');
add_action('admin_init', 'erp_custom_menu_class');

register_activation_hook(__FILE__, 'active_plugin');
register_deactivation_hook(__FILE__, 'deactive_plugin');

function active_plugin() {
    global $wpdb;
    $sql = file_get_contents(ERP_PLUGIN_DIR . "/db/install.sql");
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    if (!wp_next_scheduled(ERP_SYNC_EVENT)) {
        wp_schedule_event(strtotime('+ 5 minutes'), '5_minutes', ERP_SYNC_EVENT);
    }
}

function deactive_plugin() {
    global $wpdb;
    $sql = file_get_contents(ERP_PLUGIN_DIR . "/db/uninstall.sql");
    $wpdb->query($sql);
    wp_clear_scheduled_hook(ERP_SYNC_EVENT);
}


function erp_setup_admin_menu() {
    /*
       ROLE settings
       Administrator:   view/add/edit/delete to all
       Accounting:      view/add/edit to all
       Estimator:       view/add/edit to all except settings
       Worker:          view/edit estimates, Routes
    */
    // Root menu
    add_menu_page(
        'ER Planner Pro Dashboard',
        'ER Planner Pro',
        'manage_options',
        ROOT_MENU_SLUG,
        'erp_load',
        'dashicons-chart-line',
        11
    );
    // Sub menus
    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Referral',
        'New Referral',
        'manage_options',
        ROOT_MENU_SLUG . '#new-referral',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'List Referrals',
        'Referrals',
        'manage_options',
        ROOT_MENU_SLUG . '#referrals',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Referral Route',
        'New Referral Route',
        'manage_options',
        ROOT_MENU_SLUG . '#new-referral-route',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'List Referral Routes',
        'Referral Routes',
        'manage_options',
        ROOT_MENU_SLUG . '#referral-routes',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Estimate',
        'New Estimate',
        'manage_options',
        ROOT_MENU_SLUG . '#new-estimate',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'List Estimates',
        'Estimates',
        'manage_options',
        ROOT_MENU_SLUG . '#estimates',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'New Estimate Route',
        'New Estimate Route',
        'manage_options',
        ROOT_MENU_SLUG . '#new-estimate-route',
        'erp_load'
    );
    add_submenu_page(
        ROOT_MENU_SLUG,
        'List Estimate Routes',
        'Estimate Routes',
        'manage_options',
        ROOT_MENU_SLUG . '#estimate-routes',
        'erp_load'
    );

    add_submenu_page(
        NULL,
        'Estimate and route planner',
        'Estimate and route planner configuration',
        'manage_options',
        ERP_CONFIG_PAGE_SLUG,
        'erp_load_config_page'
    );

    $currentUser = wp_get_current_user();
    if ($currentUser && in_array('administrator', $currentUser->roles)) {
        add_submenu_page(
            ROOT_MENU_SLUG,
            'Settings',
            'Settings',
            'manage_options',
            ROOT_MENU_SLUG . '#settings',
            'erp_load'
        );
    }
    // Remove duplicate menu
    remove_submenu_page(ROOT_MENU_SLUG, ROOT_MENU_SLUG);
}

function erp_load_config_page() {
    require_once ERP_PLUGIN_DIR . '/templates/config.php';
}

function current_user_is_admin() {
    $currentUser = wp_get_current_user();
    return $currentUser && in_array('administrator', $currentUser->roles);
}

function erp_load() {
    require_once 'init.php';
    $templatePath = '';
    // FIXME: optimize here
    if (ERPConfig::checkQuickbookAppConfig()) { // Check has QB app consumer config
        if (ERPConfig::checkQuickbookAuthenticated() && ERPConfig::checkQuickbookOauthTokenValid()) {
            $templatePath = 'layout.php';
        } else {
            if (current_user_is_admin()) {
                $templatePath = 'quickbooks-authenticate.php';
            } else {
                $templatePath = 'config-miss.php';
            }
        }
    } else {
        if (current_user_is_admin()) {
            $templatePath = 'layout.php';
        } else {
            $templatePath =  'config-miss.php';
        }
    }
    require_once TEMPLATES_DIR . $templatePath;
}

function erp_enqueue_scripts() {
    if (is_plugin_page(ROOT_MENU_SLUG)) {
        if (ERP_ENABLE_DEBUG) {
            $libJS = [
                'signature-pad'     => 'js/lib/signature_pad.js',
                'dropzone'          => 'js/lib/dropzone.js',
                'lodash'            => 'js/lib/lodash.js',
                'bootbox'           => 'js/lib/bootbox.js',
                'toastr'            => 'js/lib/toastr.js',
                'bootstrap'         => 'js/lib/bootstrap.js',
                'selectize'         => 'js/lib/selectize.js',
                'angular-core'      => 'js/lib/angular.js',
                'angular-animate'   => 'js/lib/angular-animate.js',
                'angular-route'     => 'js/lib/angular-route.js',
                'angular-sanitize'  => 'js/lib/angular-sanitize.js',
                'angular-selectize' => 'js/lib/angular-selectize.js',
                'angular-signature-pad' => 'js/lib/ngSignaturePad.js',
                'angular-sortable'      => 'js/lib/ng-sortable.js',
                'angular-draggable'     => 'js/lib/ng-draggable.js',
                'angular-google-maps'   => 'js/lib/angular-google-maps.js',
                'angular-google-maps-dev-mapped' => 'js/lib/angular-google-maps_dev_mapped.js',
                'angular-bootbox'   => 'js/lib/ngBootbox.js',
                'angular-dropzone'  => 'js/lib/angular-dropzone.js',
                'angular-messages'  => 'js/lib/angular-messages.js',
                'angular-ui-bootstrap'  => 'js/lib/ui-bootstrap-tpls-0.13.3.js',
                'angular-timeago'  => 'js/lib/angular-timeago.js'
            ];
            foreach ($libJS as $name => $path) {
                wp_register_script(
                    $name,
                    plugins_url($path, __FILE__),
                    [], null, false
                );
                wp_enqueue_script($name);
            }
        } else {
            wp_register_script(
                'erp-js-lib',
                plugins_url('js/lib.min.js', __FILE__),
                [], ERP_VERSION, false
            );
            wp_enqueue_script('erp-js-lib');
        }

        if (ERP_ENABLE_DEBUG) {
            $appJS = [
                'erp-js-app' => 'js/app/main.js',
                'erp-js-app-routes' => 'js/app/routes.js',
                'erp-js-app-factories' => 'js/app/factories.js',
                'erp-js-app-directives' => 'js/app/directives.js',
            ];

            foreach ($appJS as $name => $path) {
                wp_register_script(
                    $name,
                    plugins_url($path, __FILE__),
                    [], ERP_VERSION, false
                );
                wp_enqueue_script($name);
            }

            // Auto scan and load all files in js/app/controllers
            $appComponentLocations = [
                'js/app/estimate',
                'js/app/referral',
                'js/app/settings',
                'js/app/company_info',
                'js/app/customer',
                'js/app/employee',
                'js/app/product_service',
                'js/app/referral_route',
                'js/app/estimate_route',
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
                                    [], ERP_VERSION, false
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
                [], ERP_VERSION, false
            );
            wp_enqueue_script('erp-js-app');
        }

        wp_register_script(
            'app-geolocation',
            'http://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false',
            [], null, false
        );
        wp_enqueue_script('app-geolocation');

        wp_localize_script(
            'erp-js-app',
            'ERPApp',
            [
                'baseAPIPath' => admin_url( 'admin-ajax.php' . '?action=erp' ),
                'templatesPath' => plugins_url('templates' . '/', __FILE__),
                'baseERPPluginUrl' => ERP_PLUGIN_URL,
                'navigationClass' => ERPP_NAVIGATION_CLASS,
                'version' => ERP_VERSION
            ]
        );
    }
}

function erp_enqueue_stylesheets() {
    if (is_plugin_page(ROOT_MENU_SLUG)) {
        $libCss = [
            'bootstrap' => 'css/lib/bootstrap.min.css',
            'toastr' => 'css/lib/toastr.css',
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


function erp_query_vars($vars) {
    $vars[] = '_do';
    return $vars;
}

add_filter('query_vars', 'erp_query_vars');

function erp_parse_request($wp) {
    require_once 'init.php';

    $loger = new ERPLogger('qb_auth.log');
    if (array_key_exists('_do', $wp->query_vars)) {
        $authService = new QuickbooksAuth();
        if ($wp->query_vars['_do'] == 'startQuickbooksAuthenticate') {
            $result = $authService->start(site_url() . '?_do=verifyQuickbooksAuthenticate');
            $_SESSION['oauth_token_secret'] = null;
            if($result['success']) {
                $_SESSION['oauth_token_secret'] = $result['oauth_token_secret'];
                wp_redirect($result['redirect_url']); exit;
            } else {
                wp_die("Error while authenticating with QB");
            }
        } elseif ($wp->query_vars['_do'] == 'verifyQuickbooksAuthenticate') {
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
                    require_once TEMPLATES_DIR . 'quickbooks-authenticate-success.php';
                } else {
                    wp_die("Could not saving Quickbooks authenticate keys to database!");
                }
            } else {
                wp_die("Error while get access token from QBO");
            }
        }
    }
}
add_action('parse_request', 'erp_parse_request');


/* Cronjob */
/* Add custom interval for cron */
function erp_cron_intervals($schedules)  {
    $schedules['5_minutes'] = [
        'interval' => 300,
        'display' => '5 Minutes'
    ];
    return $schedules;
}
add_filter('cron_schedules', 'erp_cron_intervals');
add_action(ERP_SYNC_EVENT, 'erp_start_sync');

function erp_start_sync() {
    require_once ERP_PLUGIN_DIR . "/schedule/sync.php";
}
?>
