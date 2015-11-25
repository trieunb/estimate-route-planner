angular.module('Erp')
    .directive('formEstimateRoute', ['APP_CONFIG', 'erpLocalStorage', 'erpOptions', formEstimateRoute]);

function formEstimateRoute(APP_CONFIG, erpLocalStorage, erpOptions) {
    return {
        restrict: 'E',
        templateUrl: APP_CONFIG.templatesPath + 'estimate-route/form.html',
        controller: ['$scope', function($scope) {
            $scope.employees = [];
            $scope.routeStatuses = erpOptions.routeStatuses;
            erpLocalStorage.getEmployees()
                .then(function(data) {
                    $scope.employees = data;
                });
        }]
    };
}
