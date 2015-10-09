angular
    .module('Erp')
    .factory('customerFactory', ['$http', customerFactory]);

function customerFactory($http) {

    return {
        all: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: { _do: 'getCustomers' }
            });
        },
        show: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'showCustomer',
                data: { id: id }
            });
        }
    };
}
