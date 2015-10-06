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

        // Estimate route
        'erpp_create_estimate_routes' : '^/new-estimate-route$',
        'erpp_edit_estimate_routes': '^/edit-estimate-route/\\d+$',
        'erpp_list_estimate_routes': '^/estimate-routes$',

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
        return currentUserCaps[cap] === true;
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

/**
 * For geo location
 */
angular
    .module('Erp')
    .service('erpGeoLocation', ['$q', erpGeoLocation]);

function erpGeoLocation($q) {
    var geocoder = new google.maps.Geocoder();

    /**
     * Resolve the given address to Location object
     */
    this.resolve = function(address) {
        return $q(function(success, fails) {
            geocoder.geocode( { address: address }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                    success(results[0].geometry.location);
                } else {
                    fails();
                }
            });
        });
    };
}


/**
 * For options use in form
 */
angular
    .module('Erp')
    .value('erpOptions', {
        sortCrewRoute: [
            {
               label: 'Custom',
               value: ''
            },
            {
               label: 'Total',
               value: 'total'
            },
            {
               label: 'Due date',
               value: 'expiration_date'
            }
        ],

        sortEstimateRoute: [
            {
                label: 'Custom',
                value: ''
            },
            {
                label: 'Status',
                value: 'status'
            },
            {
                label: 'Date Requested',
                value: 'date_requested'
            }
        ],
        referralStatuses: [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Assigned',
                label: 'Assigned'
            },
            {
                value: 'Completed',
                label: 'Completed'
            }
        ],
        routeStatuses: [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Assigned',
                label: 'Assigned'
            },
            {
                value: 'Completed',
                label: 'Completed'
            }
        ],
        estimateStatuses: [
            {
                value: 'Pending',
                label: 'Pending'
            },
            {
                value: 'Accepted',
                label: 'Accepted'
            },
            {
                value: 'Completed',
                label: 'Completed/WFI' // NOTE: Quickbooks still shows Accepted
            },
            {
                value: 'Closed',
                label: 'Closed'
            },
            {
                value: 'Rejected',
                label: 'Rejected'
            }
        ]
    });
