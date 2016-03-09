angular
    .module('Erp')
    .factory('jobRequestFactory', ['$http', jobRequestFactory]);

function jobRequestFactory($http) {
    return {
        save: function(referral) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'addReferral',
                data: referral
            });
        },
        list: function(query) {
            return $http.get(ERPApp.baseAPIPath, {
                params: Object.assign(query, {_do: 'getReferrals'})
            });
        },
        listPending: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getPendingReferrals'
                }
            });
        },
        show: function(id) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'showReferral',
                data: {id : id}
            });
        },
        update: function(referral) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateReferral',
                data: referral
            });
        },
        updateStatus: function(data) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateReferralStatus',
                data: data
            });
        },
    };
}
