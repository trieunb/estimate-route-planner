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
    $scope.setPageTitle('Work Order');
    $scope.route = {
        assignedEstimates: []
    };
    $scope.work_order = {};
    $scope.route_id = $routeParams.id;

    crewRouteFactory.get($scope.route_id)
        .success(function(routeData) {
            $scope.route = routeData;
            $scope.route.assignedEstimates = [];

            // Get saved work order
            crewRouteFactory.showWorkOrder($scope.route_id)
                .success(function(workOrderData) {
                    $scope.work_order = workOrderData;
                });

            // Get assigned estimates
            estimateFactory.listAssigedToRoute($scope.route_id).
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
                        angular.forEach(estimate.lines, function(line) {
                            line.is_empty = !line.product_service_id &&
                                !line.description;
                            if (!line.is_empty) {
                                line.worker_order_line = '';
                                if (line.qty) {
                                    line.worker_order_line += line.qty + ' - ';
                                }
                                if (line.description !== null) {
                                    line.worker_order_line += line.description;
                                }
                            }
                        });
                    });
                });
        });

    $scope.print = function() {
        window.print();
    };

    $scope.save = function() {
        var data = {};
        data.route_id = $scope.route_id;
        data.equipment_list = $scope.work_order.equipment_list;
        data.start_time = $scope.work_order.start_time;
        crewRouteFactory.saveWorkOrder(data)
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            });
    };
}
