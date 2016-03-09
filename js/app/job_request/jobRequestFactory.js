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
        list: function(page, status, keyword) {
            return $http.get(ERPApp.baseAPIPath, {
                params: {
                    _do: 'getReferrals',
                    page: page,
                    status: status,
                    keyword: keyword
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
