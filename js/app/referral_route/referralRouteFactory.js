angular
    .module('Erp')
    .factory('referralRouteFactory', ['$http', referralRouteFactory]);

function referralRouteFactory($http) {
    return {
        get: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'getReferralRoute',
                data: {id: id}
            });
        },
        all: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getReferralRoutes'
                }
            });
        },
        save: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'saveReferralRoute',
                data: data
            });
        },
        update: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateReferralRoute',
                data: data
            });
        },
        recent: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getRecentReferralRoutes'
                }
            });
        }
    };
}
