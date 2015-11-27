angular.module('Erp')
    .factory('classFactory', ['$http', classFactory]);

function classFactory($http) {
    return {
        all: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getClasses'}
            });
        }
    };
}
