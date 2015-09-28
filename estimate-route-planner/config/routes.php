<?php
return [
    'getSessionData'        => 'App@sessionData',
    'getCustomers'          => 'Customer@index',
    'showCustomer'          => 'Customer@show',
    'addCustomer'           => 'Customer@add',

    'getEstimates'          => 'Estimate@index',
    'addEstimate'           => 'Estimate@add',
    'showEstimate'          => 'Estimate@show',
    'updateEstimate'        => 'Estimate@update',
    'uploadAttachment'      => 'Estimate@uploadAttachment',
    'deleteAttachment'      => 'Estimate@deleteAttachment',
    'getUnassignedEstimates'  => 'Estimate@unassigned',
    'printEstimate'         => 'Estimate@printPDF',
    'sendEstimate'         => 'Estimate@sendEstimate',

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
    'getCompanyInfo'        => 'CompanyInfo@get',
    'getSetting'            => 'Preference@getSetting',
    'uploadLogo'            => 'CompanyInfo@uploadLogo',

    'getEmployees'          => 'Employee@index',

    // Referral routes
    'getRecentReferralRoutes' => 'ReferralRoute@recent',
    'getReferralRoutes'     => 'ReferralRoute@index',
    'getReferralRoute'      => 'ReferralRoute@show',
    'saveReferralRoute'     => 'ReferralRoute@save',
    'updateReferralRoute'   => 'ReferralRoute@update',
    'printReferralRoute'    => 'ReferralRoute@printRoute',

    // Estimate routes
    'getRecentEstimateRoutes' => 'EstimateRoute@recent',
    'getEstimateRoutes'       => 'EstimateRoute@index',
    'getEstimateRoute'        => 'EstimateRoute@show',
    'saveEstimateRoute'       => 'EstimateRoute@save',
    'updateEstimateRoute'     => 'EstimateRoute@update',
    'printEstimateRoute'      => 'EstimateRoute@printRoute',

    'getSyncSetting'          => 'QuickbooksSync@getSetting',
    'saveSyncSetting'         => 'QuickbooksSync@saveSetting',
    'syncAll'                 => 'QuickbooksSync@syncAll',
];
?>
