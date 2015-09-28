angular
    .module('Erp')
    .controller('ConfigCtrl', ['$scope', '$rootScope', '$window', 'settingsFactory', ConfigCtrl]);

function ConfigCtrl($scope, $rootScope, $window, settingsFactory) {
    $scope.config = {};
    $rootScope.isBusy = true;

    settingsFactory.get().
        success(function(response) {
            $scope.config = response;
        }).
        then(function() {
            $rootScope.isBusy = false;
        });

    $scope.submitForm = function() {
        $rootScope.isBusy = true;
        settingsFactory.save($scope.config)
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
    }
}
