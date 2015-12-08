<?php

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

function erp_start_session() {
    if (session_status() == PHP_SESSION_NONE) {
        session_save_path(ERP_PLUGIN_DIR . '/tmp');
        @session_start();
        if (session_status() == PHP_SESSION_NONE) {
            wp_die("Error: PHP session could not start. Please check your config.");
        }
    }
}

function erp_load() {
    require_once ERP_PLUGIN_DIR . 'init.php';
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
    require_once ERP_TEMPLATES_DIR . $templatePath;
}

?>
