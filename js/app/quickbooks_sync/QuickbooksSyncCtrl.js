angular
    .module('Erp')
    .controller(
        'QuickbooksSyncCtrl',
        [
            '$scope',
            '$rootScope',
            '$window',
            'quickbooksSyncFactory',
            '$ngBootbox',
            '$window',
            QuickbooksSyncCtrl
        ]
    );

function QuickbooksSyncCtrl($scope, $rootScope, $window, quickbooksSyncFactory, $ngBootbox, $window) {
    $scope.setPageTitle('Quickbooks Synchronize');

    $scope.info = {};
    quickbooksSyncFactory.getInfo().
        success(function(response) {
            var info = response;
            if (info.last_sync_at) {
                info.last_sync_at = new Date(info.last_sync_at + ' ' + ERPApp.timezone);
            }
            $scope.info = info;
        });

    $scope.startSync = function() {
        toastr['warning']('Please wait, this might take few minutes to to finish ..');
        quickbooksSyncFactory.startSync()
            .success(function(response) {
                if (response.success) {
                    toastr['success'](response.message);
                    $scope.info.last_sync_at = new Date(response.data.last_sync_at + ' ' + ERPApp.timezone);
                    // Expire cached data
                    $rootScope.customers = undefined;
                    $rootScope.employees = undefined;
                    $rootScope.loadSharedData();
                } else {
                    var msg = response.message || 'An error has occurred while synchrinize data';
                    toastr['error'](msg);
                }
            })
            .error(function(data, status, header, config) {
                if (status == 408) {
                    toastr['warning']("Timeout waiting for response!");
                } else {
                    toastr['error']("An error has occurred while synchrinize data");
                }
            });
    }

    $scope.reconnect = function() {
        $ngBootbox.confirm(
            "Are you sure? This action cannot be undone. It's will clear the current OAuth keys, and you have to reconnect."
        )
        .then(function() {
            quickbooksSyncFactory.reconnect()
                .success(function(response) {
                    if (response.success) {
                        // Reload to goto reconnect
                        $window.location.reload();
                    } else {
                        toastr['error'](response.message);
                    }
                })
                .error(function(data, status, header, config) {
                    toastr['error']("An error has occurred");
                });
        });
    }
}
