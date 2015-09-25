angular
    .module('Erp')
    .controller(
        'WorkOrderCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            'estimateRouteFactory',
            WorkOrderCtrl
        ]
    );

function WorkOrderCtrl($scope, $rootScope, $routeParams, estimateRouteFactory) {
    $scope.setPageTitle('Create Work Order');
    $scope.route = {};
    estimateRouteFactory.get($routeParams.id).
        success(function(data) {
            $scope.route = data;
        });
}
