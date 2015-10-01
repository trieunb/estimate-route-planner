angular
    .module('Erp')
    .factory('employeeFactory', ['$http', employeeFactory]);

function employeeFactory($http) {
    return {
        all: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getEmployees'}
            });
        }
    };
}
