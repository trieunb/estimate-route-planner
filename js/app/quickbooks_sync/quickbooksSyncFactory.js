angular
    .module('Erp')
    .factory('quickbooksSyncFactory', ['$http', quickbooksSyncFactory]);

function quickbooksSyncFactory($http) {
    return {
        getInfo: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getSyncInfo'}
            });
        },
        saveSetting: function(setting) {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'saveSyncSetting',
                data: setting
            });
        },
        startSync: function() {
            return $http.post(ERPApp.baseAPIPath,
                {
                    _do: 'syncAll'
                },
                { timeout: 3600000 }
            );
        },
        reconnect: function() {
            return $http.post(ERPApp.baseAPIPath, {
                _do: 'reconnectQuickbooks'
            });
        }
    };
}
