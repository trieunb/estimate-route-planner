angular
    .module('Erp')
    .factory('quickbooksSyncFactory', ['$http', quickbooksSyncFactory]);

function quickbooksSyncFactory($http) {
    return {
        getSetting: function() {
            return $http.get(ERPApp.baseAPIPath, {
                params: {_do: 'getSyncSetting'}
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
                { timeout: 50000 }
            );
        }
    }
}
