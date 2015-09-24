angular
    .module('Erp')
    .service('userPermission', ['USER_CAPABILITIES', userPermission]);

function userPermission(USER_CAPABILITIES) {
    var currentUserCaps = {};
    angular.copy(USER_CAPABILITIES, currentUserCaps);
    var capsToPaths = {
        'erpp_access' : '^/$',

        // Estimate
        'erpp_create_estimates': '^/new-estimate$',
        'erpp_edit_estimates' : '^/edit-estimate/\\d+$',
        'erpp_list_estimates' : '^/estimates$',
        'erpp_list_estimates' : '^/estimates/page/\\d+$',

        // Referral
        'erpp_create_referrals' : '^/new-referral$',
        'erpp_edit_referrals' : '^/edit-referral/\\d+$',
        'erpp_list_referrals' : '^/referrals$',

        // Referral route
        'erpp_create_referral_routes' : '^/new-referral-route$',
        'erpp_edit_referral_routes': '^/edit-referral-route/\\d+$',
        'erpp_list_referral_routes': '^/referral-routes$',

        // Estimate route
        'erpp_create_estimate_routes': '^/new-estimate-route$',
        'erpp_edit_estimate_routes' : '^/edit-referral-route/\\d+$',
        'erpp_list_estimate_routes' :'^/estimate-routes$',

        // Settings
        'erpp_settings' : '^/settings$',
        'erpp_settings' : '^/company-info$',
        'erpp_settings' : '^/quickbooks-sync$'
    };

    this.capForPath = function(requestPath) {
        for (var cap in capsToPaths) {
            var rge = new RegExp(capsToPaths[cap]);
            if (rge.test(requestPath)) {
                return cap;
            }
        }
    };

    this.hasCap = function(cap) {
        return currentUserCaps[cap] == true;
    };

    this.canAccessTo = function(path) {
        var cap = this.capForPath(path);
        if ('undefined' !== typeof(cap)) {
            return this.hasCap(cap);
        } else {
            // Allow the path not list in restriction
            return true;
        }
    };
}
