angular
    .module('Erp')
    .factory('settingsFactory', ['$http', settingsFactory]);

function settingsFactory($http) {
    return {
        sendTestEmail: function(to, setting) {
            return $http.post(ERPApp.baseAPIPath,
                {
                    _do:  'sendTestEmail',
                    data: {
                        to: to,
                        setting: setting
                    }
                },
                { timeout: 10000 } // Set timeout to 10 secs
            );
        },
        save: function(setting) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'updateSetting',
                data: setting
            });
        },
        get: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getSetting'}
            });
        },
        startSync: function() {
            return $http.post(ERPApp.baseAPIPath,
                {
                    _do: 'syncAll'
                },
                { timeout: 50000 }
            );
        }
    }
}
