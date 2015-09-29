<?php
return [
    'getSessionData'        => 'App@sessionData',
    'getSharedData'         => 'App@sharedData',
    'getCustomers'          => 'Customer@index',
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

    // Referral routes
    'getRecentReferralRoutes' => 'ReferralRoute@recent',
    'getReferralRoutes'     => 'ReferralRoute@all',
    'filterReferralRoutes'  => 'ReferralRoute@index',
    'getReferralRoute'      => 'ReferralRoute@show',
    'saveReferralRoute'     => 'ReferralRoute@save',
    'updateReferralRoute'   => 'ReferralRoute@update',

    // Estimate routes
    'getRecentEstimateRoutes' => 'EstimateRoute@recent',
    'getEstimateRoutes'       => 'EstimateRoute@index',
    'getEstimateRoute'        => 'EstimateRoute@show',
    'saveEstimateRoute'       => 'EstimateRoute@save',
    'updateEstimateRoute'     => 'EstimateRoute@update',

    // Settings
    'getSyncInfo'             => 'QuickbooksSync@getInfo',
    'saveSyncSetting'         => 'QuickbooksSync@saveSetting',
    'syncAll'                 => 'QuickbooksSync@syncAll',
    'reconnectQuickbooks'     => 'QuickbooksSync@reconnect',
];
?>
