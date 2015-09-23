angular
    .module('Erp')
    .factory('dataFactory', ['$http', dataFactory]);

function dataFactory($http) {
    return {
        getSessionData: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: { _do: 'getSessionData' }
            });
        },
        getSharedData: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: { _do: 'getSharedData' }
            });
        }
    }
}
