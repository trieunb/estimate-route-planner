angular.module('Erp')
    .factory('productServiceFactory', ['$http', productServiceFactory]);

function productServiceFactory($http) {
    return {
        all: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getProductServices'}
            });
        }
    };
}
