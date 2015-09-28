<?php
/**
 * This file contains global functions
 */

/**
 * Dump variable and exit
 */
if (!function_exists('dd')) {
    function dd($var) {
        var_dump($var);
        exit();
    }
}

/**
 * Print variable and exit
 */
if (!function_exists('pd')) {
    function pd($var) {
        print_r($var);
        exit();
    }
}

/**
 * Generate absolute url for assets(likes images, attachments ..) insides plugin
 */
function erp_asset_url($relativePath) {
    return plugins_url($relativePath,  ERP_ROOT_DIR . '/init.php');
}

?>
