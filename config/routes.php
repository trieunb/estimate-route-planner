<?php
return [
    'getSharedData'         => 'App@sharedData',
    'getCustomers'          => 'Customer@index',
    'getProductServices'    => 'App@productServices',
    'showCustomer'          => 'Customer@show',

    'getEstimates'          => 'Estimate@index',
    'addEstimate'           => 'Estimate@add',
    'showEstimate'          => 'Estimate@show',
    'updateEstimate'        => 'Estimate@update',
    'uploadAttachment'      => 'Estimate@uploadAttachment',
    'deleteAttachment'      => 'Estimate@deleteAttachment',
    'getUnassignedEstimates'=> 'Estimate@unassigned',
    'getAssignedEstimates'  => 'Estimate@assigned',
    'printEstimate'         => 'Estimate@printPDF',
    'sendEstimate'          => 'Estimate@sendEstimate',
    'getEstimateAttachments'=> 'Estimate@attachments',

    'getAttachment'         => 'Attachment@show',

    'printReferral'         => 'Referral@printPDF',
    'getReferrals'          => 'Referral@index',
    'getPendingReferrals'   => 'Referral@pending',
    'addReferral'           => 'Referral@add',
    'showReferral'          => 'Referral@show',
    'updateReferral'        => 'Referral@update',
    'updateReferralStatus'  => 'Referral@updateStatus',

    'sendTestEmail'         => 'Preference@sendTestEmail',
    'updateCompanyInfo'     => 'CompanyInfo@update',
    'updateSetting'         => 'Preference@updateSetting',
    'saveAppConfig'         => 'Preference@saveAppConfig',
    'getAppConfig'          => 'Preference@getAppConfig',
    'getCompanyInfo'        => 'CompanyInfo@get',
    'getSetting'            => 'Preference@getSetting',
    'uploadLogo'            => 'CompanyInfo@uploadLogo',

    'getEmployees'          => 'Employee@index',

    // Estimate routes
    'getRecentEstimateRoutes'   => 'EstimateRoute@recent',
    'getEstimateRoutes'         => 'EstimateRoute@all',
    'filterEstimateRoutes'      => 'EstimateRoute@index',
    'getEstimateRoute'          => 'EstimateRoute@show',
    'saveEstimateRoute'         => 'EstimateRoute@save',
    'updateEstimateRoute'       => 'EstimateRoute@update',

    // Crew routes
    'getRecentCrewRoutes' => 'CrewRoute@recent',
    'getCrewRoutes'       => 'CrewRoute@index',
    'getCrewRoute'        => 'CrewRoute@show',
    'saveCrewRoute'       => 'CrewRoute@save',
    'updateCrewRoute'     => 'CrewRoute@update',

    // Settings
    'getSyncInfo'             => 'QuickbooksSync@getInfo',
    'saveSyncSetting'         => 'QuickbooksSync@saveSetting',
    'syncAll'                 => 'QuickbooksSync@syncAll',
    'reconnectQuickbooks'     => 'QuickbooksSync@reconnect',
];
?>
