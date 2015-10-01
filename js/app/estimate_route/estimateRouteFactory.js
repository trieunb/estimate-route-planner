angular
    .module('Erp')
    .factory('estimateRouteFactory', ['$http', estimateRouteFactory]);

function estimateRouteFactory($http) {
    return {
        get: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'getEstimateRoute',
                data: {id: id}
            });
        },
        filter: function(query) {
            return $http.get(ERPApp.baseAPIPath, {
                params: query
            });
        },
        all: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getEstimateRoutes'}
            });
        },
        save: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'saveEstimateRoute',
                data: data
            });
        },
        update: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateEstimateRoute',
                data: data
            });
        },
        recent: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getRecentEstimateRoutes'
                }
            });
        }
    };
}
