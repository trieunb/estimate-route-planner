angular
    .module('Erp')
    .controller('SettingsCtrl', ['$scope', 'settingsFactory', SettingsCtrl]);

function SettingsCtrl($scope, settingsFactory) {
    $scope.setPageTitle('Settings');
    $scope.setting = {};

    settingsFactory.get().
        success(function(response) {
            $scope.setting = response;
        });

    $scope.submitForm = function() {
        settingsFactory.save($scope.setting)
            .success(function(response) {
                if (response.success) {
                    toastr['success'](response.message);
                } else {
                    toastr['error'](response.message);
                }
            });
    };

    $scope.sendTestEmail = function() {
        var to = prompt("Please enter email to receive:");
        if (to && to.trim().length > 0) {
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
                });
        }
    };
}
