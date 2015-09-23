angular
    .module('Erp')
    .controller(
        'AppConfigCtrl',
        [
            '$scope',
            '$rootScope',
            '$window',
            'settingsFactory',
            AppConfigCtrl
        ]
    );

function AppConfigCtrl($scope, $rootScope, $window, settingsFactory) {
    $scope.config = {};
    $scope.step = 1;

    settingsFactory.getAppConfig()
        .success(function(response) {
            $scope.config = response;
        });

    $scope.submitForm = function() {
        settingsFactory.saveAppConfig($scope.config)
            .success(function(response) {
                if (response.success) {
                    $scope.step = 2;
                } else {
                    toastr['error']('An error occurred while saving the configuration');
                }
            })
            .error(function() {
                toastr['error']('An error occurred while saving the configuration');
            });
    }

    $scope.backToSetConsumerKeys = function() {
        $scope.step = 1;
    }
}
