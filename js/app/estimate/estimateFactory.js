angular
    .module('Erp')
    .factory('estimateFactory', ['$http', estimateFactory]);

function estimateFactory($http) {
    return {
        save: function(estimate) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'addEstimate',
                data: estimate
            });
        },
        listAssigedToRoute: function(routeId) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'getAssignedEstimates',
                data: {id: routeId}
            });
        },
        list: function(query) {
            return $http.get(ERPApp.baseAPIPath, {
                params: query
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
        listAssignable: function(){
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getAssignableEstimates'
                }
            });
        },
        sendMail: function(mailData) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'sendEstimate',
                data: mailData
            });
        },
        attachments: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'getEstimateAttachments',
                data: {id: id}
            });
        }
    };
}
