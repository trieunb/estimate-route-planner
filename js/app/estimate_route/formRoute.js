angular.module('Erp')
    .directive('formRoute', ['APP_CONFIG', 'erpLocalStorage', formRoute]);

function formRoute(APP_CONFIG, erpLocalStorage) {
    return {
        restrict: 'E',
        templateUrl: APP_CONFIG.templatesPath + 'estimate-route/form.html',
        controller: ['$scope', function($scope) {
            $scope.employees = [];
            erpLocalStorage.getEmployees()
                .then(function(data) {
                    $scope.employees = data;
                });
        }]
    };
}
