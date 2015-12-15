<?php
/**
* Plugin Name: Estimate and Route Planner Pro
* Description: Estimate and route planning
* Version: 1.7.3
* Author: SFR Software
* Author URI: http://sfr-creative.com/
*/
define('ERP_VERSION', '1.7.3');
define('ERP_PLUGIN_URL', plugin_dir_url(__FILE__));  // Http URL to plugin
define('ERP_PLUGIN_DIR', plugin_dir_path(__FILE__)); // Physical root path of plugin
define('ROOT_MENU_SLUG', 'erpp');
define('ERPP_NAVIGATION_CLASS', 'estimate-route-planner-menu');
define('ERP_PLUGIN_NAME', 'ER Planner Pro');
define('ERP_PLUGIN_SCRIPT', __FILE__);

require_once(ERP_PLUGIN_DIR . '/config/plugin.php');

$includes = [
    'functions.php',
    'active.php',
    'menu.php',
    'scripts.php',
    'stylesheets.php',
    'request_handler.php'
];

foreach($includes as $inc) {
    require_once ERP_PLUGIN_DIR . '/includes/' . $inc;
}

add_action('admin_menu', 'erp_setup_admin_menu');
add_action('admin_enqueue_scripts', 'erp_enqueue_scripts');
add_action('admin_enqueue_scripts', 'erp_enqueue_stylesheets');
add_action('admin_init', 'erp_custom_menu_class');
add_action('wp_ajax_erp', 'erp_ajax_handler');
add_filter('query_vars', 'erp_query_vars');
add_action('parse_request', 'erp_parse_request');
?>
