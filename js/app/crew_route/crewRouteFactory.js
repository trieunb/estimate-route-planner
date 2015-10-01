angular
    .module('Erp')
    .factory('crewRouteFactory', ['$http', crewRouteFactory]);

function crewRouteFactory($http) {
    return {
        get: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'getEstimateRoute',
                data: {id: id}
            });
        },
        all: function(query) {
            return $http.get(ERPApp.baseAPIPath, {
                params: query
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
        },
    };
}
