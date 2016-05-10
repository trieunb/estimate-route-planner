<?php
function erp_enqueue_stylesheets($hook) {
    if (strpos($hook, ROOT_MENU_SLUG) !== false) {
        $libCss = [
            'bootstrap' => 'css/lib/bootstrap.min.css',
            'toastr'    => 'css/lib/toastr.css',
            'selectize' => 'css/lib/selectize.bootstrap3.css'
        ];
        foreach ($libCss as $name => $path) {
            wp_register_style(
                $name,
                plugins_url($path, ERP_PLUGIN_SCRIPT),
                false,
                null
            );
            wp_enqueue_style($name);
        }
        wp_register_style(
            'erp_main_stylesheet',
            plugins_url('css/style.css', ERP_PLUGIN_SCRIPT),
            false,
            ERP_VERSION
        );
        wp_enqueue_style('erp_main_stylesheet');
    }
}

?>
