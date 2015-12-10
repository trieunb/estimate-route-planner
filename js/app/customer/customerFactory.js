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
        create: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'createCustomer',
                data: data
            });
        },
        show: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'showCustomer',
                data: { id: id }
            });
        },
        update: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateCustomer',
                data: data
            });
        }
    };
}
