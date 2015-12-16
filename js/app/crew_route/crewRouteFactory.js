angular
    .module('Erp')
    .factory('crewRouteFactory', ['$http', crewRouteFactory]);

function crewRouteFactory($http) {
    return {
        get: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'getCrewRoute',
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
                _do: 'saveCrewRoute',
                data: data
            });
        },
        update: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateCrewRoute',
                data: data
            });
        },
        showWorkOrder: function(routeId) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'showWorkOrder',
                data: {route_id: routeId}
            });
        },
        deleteWorkOrder: function(routeId) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'deleteWorkOrder',
                data: {route_id: routeId}
            });
        },
        saveWorkOrder: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'saveWorkOrder',
                data: data
            });
        },
        recent: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getRecentCrewRoutes'
                }
            });
        },
    };
}
