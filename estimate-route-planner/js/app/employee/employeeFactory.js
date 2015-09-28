angular
    .module('Erp')
    .factory('employeeFactory', ['$http', employeeFactory]);

function employeeFactory($http) {
    return {
        all: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getEmployees'}
            });
        },
        get: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'getEmployee',
                data: {id : id}
            });
        }
    };
}
