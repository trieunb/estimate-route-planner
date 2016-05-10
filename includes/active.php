<?php
/**
 * Hooks for activing/deactivating plugin
 */

function active_plugin() {
    /* Insert tables to DB */
    global $wpdb;
    $sql = file_get_contents(ERP_PLUGIN_DIR . '/db/install.sql');
    // $wpdb->query($sql);

    /* Add all plugin capabilities to all roles */
    $capsRegister = new ERPCapabilityRegister();
    $capsRegister->register(erp_get_capabilities());
}

function deactive_plugin() {
    /* Remove tables from DB */
    global $wpdb;
    $sql = file_get_contents(ERP_PLUGIN_DIR . '/db/uninstall.sql');
    // $wpdb->query($sql);

    /* Remove all role capabitities */
    $capsRegister = new ERPCapabilityRegister();
    $capsRegister->unregister(erp_get_capabilities());
}

register_activation_hook(ERP_PLUGIN_SCRIPT, 'active_plugin');
register_deactivation_hook(ERP_PLUGIN_SCRIPT, 'deactive_plugin');
?>
