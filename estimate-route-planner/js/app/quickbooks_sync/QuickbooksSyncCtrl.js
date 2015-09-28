angular
    .module('Erp')
    .controller('QuickbooksSyncCtrl', [
        '$scope', '$rootScope', '$window', 'quickbooksSyncFactory', '$ngBootbox', QuickbooksSyncCtrl]);

function QuickbooksSyncCtrl($scope, $rootScope, $window, quickbooksSyncFactory, $ngBootbox) {
    $scope.config = {};
    $rootScope.isBusy = true;

    quickbooksSyncFactory.getSetting().
        success(function(response) {
            var config = response;
            if (config.last_sync_at) {
                config.last_sync_at = new Date(config.last_sync_at + ' GMT');
            }
            $scope.config = config;
        }).
        then(function() {
            $rootScope.isBusy = false;
        });

    $scope.submitForm = function() {
        $ngBootbox.confirm("Warning! Change the app consumer keys will need to re-authenticate with Quickbooks Online, are you sure?")
            .then(
                function() {
                    $rootScope.isBusy = true;
                    quickbooksSyncFactory.saveSetting($scope.config)
                        .success(function(response) {
                            if(response.success) {
                                $window.location.reload();
                            } else {
                                toastr['error']('An error occurred while saving the configuration');
                            }
                        })
                        .error(function() {
                            $rootScope.isBusy = false;
                         })
                        .then(function() {
                            $rootScope.isBusy = false;
                        });
                },
                function() {

                }
            );
    }

    $scope.startSync = function() {
        $rootScope.isBusy = true;
        toastr['warning']('Please wait, this might take few minutes to to finish ..');
        quickbooksSyncFactory.startSync()
            .success(function(response) {
                $rootScope.isBusy = false;
                if (response.success) {
                    toastr['success'](response.message);
                    $scope.config.last_sync_at = new Date(response.data.last_sync_at + ' GMT');
                    // TODO: reload product services
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
            })
            .then(function() {
                $rootScope.isBusy = false;
            });
    }
}
