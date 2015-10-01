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

            estimateFactory.listAssigedToRoute(routeId).
                success(function(responseData) {
                    $scope.route.assignedEstimates = responseData;
                });
        });

    $scope.print = function() {
        window.print();
    };
}
