angular.module('Erp')
    .directive('formCrewRoute', ['APP_CONFIG', 'erpOptions', formCrewRoute]);

function formCrewRoute(APP_CONFIG, erpOptions) {
    return {
        restrict: 'E',
        templateUrl: APP_CONFIG.templatesPath + 'crew-route/form.html',
        controller: ['$scope', function($scope) {
            $scope.employees = [];
            $scope.routeStatuses = erpOptions.routeStatuses;
        }]
    };
}
