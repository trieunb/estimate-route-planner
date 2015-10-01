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

        // Job request
        'erpp_create_job_requests' : '^/new-job-request$',
        'erpp_edit_job_requests' : '^/edit-job-request/\\d+$',
        'erpp_list_job_requests' : '^/job-requests$',

        // Referral route
        'erpp_create_referral_routes' : '^/new-referral-route$',
        'erpp_edit_referral_routes': '^/edit-referral-route/\\d+$',
        'erpp_list_referral_routes': '^/referral-routes$',

        // Crew route
        'erpp_create_crew_routes': '^/new-crew-route$',
        'erpp_edit_crew_routes' : '^/edit-crew-route/\\d+$',
        'erpp_list_crew_routes' :'^/crew-routes$',

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
