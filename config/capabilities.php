<?php
/**
 * List of plugin capabilities
 * Example:
 * 'cap_id' => [                    // Required - The capability id
 *     'enable'     => true/false   // Optional - Enable or not, default is `true`
 *     'admin_only' => true/false   // Optional - Only enabled for administrator role, default is `false`
 *  ]
 */
return [
    // Basic capability to see plugin menus, pages. Shoud not change it!
    'erpp_access' => [],

    // Job request
    'erpp_create_job_requests'      => [],
    'erpp_edit_job_requests'        => [],
    'erpp_list_job_requests'        => [],
    'erpp_print_job_requests'       => [],

    // Estimate route
    'erpp_create_estimate_routes'   => [],
    'erpp_edit_estimate_routes'     => [],
    'erpp_list_estimate_routes'     => [],
    'erpp_print_estimate_routes'    => [],

    // Estimate
    'erpp_create_estimates'         => [],
    'erpp_edit_estimates'           => [],
    'erpp_list_estimates'           => [],
    'erpp_view_estimate_total'      => [],
    'erpp_print_estimates'          => [],
    'erpp_send_estimates'           => [],


    // Crew route
    'erpp_create_crew_routes'       => [],
    'erpp_edit_crew_routes'         => [],
    'erpp_list_crew_routes'         => [],
    'erpp_print_crew_routes'        => [],

    // Settings
    'erpp_settings'                 => [
        'admin_only' => true
    ],

    // Other non-resources caps

    'erpp_view_sales_estimates'     => [
        'enable' => false
    ],
    // Only shows estimate routes that assigned to the user
    'erpp_estimator_only_routes'    => [
        'enable' => false
    ],
    // Hide `Pending Routes` list so the person can't assign requests to themselves
    'erpp_hide_estimate_pending_list' => [
        'enable' => false
    ],
    //Auto permission to hide expired estimates
    'erpp_hide_expired_estimates' => [
        'enable' => false
    ]
];
?>
