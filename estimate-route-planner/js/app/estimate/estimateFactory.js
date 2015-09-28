angular
    .module('Erp')
    .factory('estimateFactory', ['$http', estimateFactory]);

function estimateFactory($http) {
    return {
        save: function(estimate) {
            console.log(estimate);
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'addEstimate',
                data: estimate
            });
        },
        list: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getEstimates'}
            });
        },
        show: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'showEstimate',
                data: {id: id}
            });
        },
        update: function(estimate){
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateEstimate',
                data: estimate
            });
        },
        listUnassigned: function(){
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getUnassignedEstimates'
                }
            });
        },
        sendMail: function(mailData) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'sendEstimate',
                data: mailData
            });
        },
    };
}
