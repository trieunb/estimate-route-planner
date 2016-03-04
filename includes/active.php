<?php
/**
 * Hooks for activing/deactivating plugin
 */

register_activation_hook(ERP_PLUGIN_SCRIPT, 'active_plugin');
register_deactivation_hook(ERP_PLUGIN_SCRIPT, 'deactive_plugin');

function active_plugin() {
    /* Insert tables to DB */
    global $wpdb;
    $sql = file_get_contents(ERP_PLUGIN_DIR . '/db/install.sql');
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    // dbDelta($sql);

    /* Add all plugin capabilities to all roles */
    global $wp_roles;
    $pluginCaps = erp_get_capabilities();
    // Skip these caps for admin role
    $exceptAdminCaps = [
        'erpp_view_sales_estimates',
        'erpp_estimator_only_routes',
        'erpp_hide_estimate_pending_list'
    ];
    $adminRoles = ['administrator', 'erppadmin', 'erpp_admin'];

    foreach ($wp_roles->get_names() as $roleName => $label) {
        $role = get_role($roleName);
        if ($role) {
            foreach ($pluginCaps as $cap => $options) {
                if (in_array($roleName, $adminRoles) && in_array($cap, $exceptAdminCaps)) {
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
    // $wpdb->query($sql);

    /* Remove all role capabitities */
    global $wp_roles;
    $pluginCaps = erp_get_capabilities();
    foreach ($wp_roles->get_names() as $roleName => $label) {
        $role = get_role($roleName);
        if ($role) {
            foreach ($pluginCaps as $cap => $options) {
                $role->remove_cap($cap);
            }
        }
    }
}
?>
