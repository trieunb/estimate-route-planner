angular
    .module('Erp')
    .controller('SettingsCtrl', ['$scope', '$rootScope', 'settingsFactory', SettingsCtrl]);

function SettingsCtrl($scope, $rootScope, settingsFactory) {
    $rootScope.pageTitle = 'Settings';
    $scope.setting = {};
    
    $rootScope.isBusy = true;
    settingsFactory.get().
        success(function(response) {
            $scope.setting = response;
        }).
        then(function() {
            $rootScope.isBusy = false;
        });

    $scope.submitForm = function() {
        $rootScope.isBusy = true;
        settingsFactory.save($scope.setting)
            .success(function(response) {
                if (response.success) {
                    toastr['success'](response.message);
                } else {
                    toastr['error'](response.message);
                }
            })
            .then(function() {
                $rootScope.isBusy = false;
            });
    };

    $scope.sendTestEmail = function() {
        var to = prompt("Please enter email to receive:");
        if (to && to.trim().length > 0) {
            $rootScope.isBusy = true;
            settingsFactory.sendTestEmail(to, $scope.setting)
                .success(function(response) {
                    if (response.success) {
                        toastr['success'](response.message);
                    } else {
                        toastr['error'](response.message);
                    }
                })
                .error(function(data, status, header, config) {
                    if (status == 408) {
                        toastr['warning']("Timeout waiting for response!");
                    } else {
                        toastr['error']("An error has occurred while sending email");
                    }
                })
                .then(function() {
                    $rootScope.isBusy = false;
                });
        }
    };

}
