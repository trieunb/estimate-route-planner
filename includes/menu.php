<?php

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
?>
