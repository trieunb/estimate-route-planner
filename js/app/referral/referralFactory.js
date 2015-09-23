angular
    .module('Erp')
    .factory('referralFactory', ['$http', referralFactory]);

function referralFactory($http) {
    return {
        save: function(referral) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'addReferral',
                data: referral
            });
        },
        list: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getReferrals'
                }
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
