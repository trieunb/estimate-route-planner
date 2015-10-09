angular
    .module('Erp')
    .factory('dataFactory', ['$http', dataFactory]);

function dataFactory($http) {
    return {
        getSharedData: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: { _do: 'getSharedData' }
            });
        }
    };
}
