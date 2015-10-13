angular
    .module('Erp')
    .controller(
        'WorkOrderCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            'crewRouteFactory',
            'estimateFactory',
            WorkOrderCtrl
        ]
    );

function WorkOrderCtrl(
        $scope,
        $rootScope,
        $routeParams,
        crewRouteFactory,
        estimateFactory) {
    $scope.setPageTitle('Create Work Order');
    $scope.route = {
        assignedEstimates: []
    };
    var routeId = $routeParams.id;
    crewRouteFactory.get(routeId).
        success(function(data) {
            $scope.route = data;
            $scope.route.assignedEstimates = [];
            estimateFactory.listAssigedToRoute(routeId).
                success(function(responseData) {
                    angular.forEach(responseData, function(estimate) {
                        estimate.estimators = [];
                        if (estimate.sold_by_1) {
                            estimate.estimators.push(estimate.sold_by_1);
                        }
                        if (estimate.sold_by_2) {
                            estimate.estimators.push(estimate.sold_by_2);
                        }
                        $scope.route.assignedEstimates.push(estimate);
                    });
                });
        });

    $scope.print = function() {
        window.print();
    };
}
