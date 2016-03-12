angular
    .module('Erp')
    .controller(
        'QuickbooksSyncCtrl',
        [
            '$scope',
            '$window',
            'quickbooksSyncFactory',
            'erpLocalStorage',
            '$ngBootbox',
            QuickbooksSyncCtrl
        ]
    );

function QuickbooksSyncCtrl(
    $scope,
    $window,
    quickbooksSyncFactory,
    erpLocalStorage,
    $ngBootbox) {

    $scope.setPageTitle('Quickbooks Synchronize');
    $scope.info = {};

    $scope.startSync = function() {
        toastr.warning('Please wait, this might take few minutes to to finish ..');
        quickbooksSyncFactory.startSync()
            .success(function(response) {
                toastr.clear();
                if (response.success) {
                    toastr.success(response.message);
                    // Expire cached data
                    erpLocalStorage.clearCustomers();
                    erpLocalStorage.clearProductServices();
                    erpLocalStorage.clearClasses();
                } else {
                    var msg = response.message || 'An error has occurred while synchrinize data';
                    toastr.error(msg);
                }
                loadInfo();
            })
            .error(function(data, status, header, config) {
                if (status == 408) {
                    toastr.warning("Timeout waiting for response!");
                } else {
                    toastr.error("An error has occurred while synchrinize data");
                    loadInfo();
                }
            });
    };

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
                        toastr.error(response.message);
                    }
                })
                .error(function(data, status, header, config) {
                    toastr.error("An error has occurred");
                });
        });
    };

    var loadInfo = function() {
        quickbooksSyncFactory.getInfo().
            success(function(response) {
                $scope.info = {};
                var info = response;
                if (info.last_sync_at) {
                    info.last_sync_at = new Date(info.last_sync_at + ' ' + ERPApp.timezone);
                }
                $scope.info = info;
            });
    };

    loadInfo();
}
