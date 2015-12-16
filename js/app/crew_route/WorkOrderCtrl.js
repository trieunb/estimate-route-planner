angular
    .module('Erp')
    .controller(
        'WorkOrderCtrl',
        [
            '$scope',
            '$routeParams',
            '$ngBootbox',
            '$window',
            'crewRouteFactory',
            'estimateFactory',
            WorkOrderCtrl
        ]
    );

function WorkOrderCtrl(
        $scope,
        $routeParams,
        $ngBootbox,
        $window,
        crewRouteFactory,
        estimateFactory) {
    $scope.setPageTitle('Work Order');

    var init = function() {
        $scope.route = {
            assignedEstimates: []
        };
        $scope.work_order = {
            equipment_list: '',
            is_saved: false,
            start_time: ''
        };
        $scope.route_id = $routeParams.id;
        crewRouteFactory.get($scope.route_id)
            .success(function(routeData) {
                $scope.route = routeData;
                $scope.route.assignedEstimates = [];

                // Get saved work order
                crewRouteFactory.showWorkOrder($scope.route_id)
                    .success(function(workOrderData) {
                        if (workOrderData.id !== undefined) {
                            $scope.work_order = workOrderData;
                            $scope.work_order.is_saved = true;
                        }
                        // Get assigned estimates
                        estimateFactory.listAssigedToRoute($scope.route_id).
                            success(function(responseData) {
                                var savedEstimatesData = [];
                                if ($scope.work_order.estimates_data !== undefined) {
                                    savedEstimatesData = $scope.work_order.estimates_data;
                                }
                                for (var e = 0; e < responseData.length; e++) {
                                    var estimate = responseData[e];
                                    var savedEstimate;
                                    if (savedEstimatesData.length) {
                                        for (var i = 0; i < savedEstimatesData.length; i++) {
                                            if (savedEstimatesData[i].id == estimate.id) {
                                                savedEstimate = savedEstimatesData[i];
                                                break;
                                            }
                                        }
                                    }

                                    if (savedEstimate) {
                                        estimate.eta = savedEstimate.eta;
                                    }

                                    estimate.estimators = [];
                                    if (estimate.sold_by_1) {
                                        estimate.estimators.push(estimate.sold_by_1);
                                    }
                                    if (estimate.sold_by_2) {
                                        estimate.estimators.push(estimate.sold_by_2);
                                    }
                                    $scope.route.assignedEstimates.push(estimate);

                                    angular.forEach(estimate.lines, function(line) {
                                        line.visible = true;

                                        line.is_empty = !line.product_service_id && // A separate line
                                            !line.description;

                                        if (!line.is_empty) {
                                            // When line is new
                                            line.worker_order_line = '';
                                            if (line.qty) {
                                                line.worker_order_line += line.qty + ' - ';
                                            }
                                            if (line.description !== null) {
                                                line.worker_order_line += line.description;
                                            }
                                        }

                                        if (savedEstimate) {
                                            line.visible = false;
                                            for (var j = 0; j < savedEstimate.lines.length; j++) {
                                                if (savedEstimate.lines[j].id == line.line_id) {
                                                    line.visible = true;
                                                    line.worker_order_line = savedEstimate.lines[j].content;
                                                    break;
                                                }
                                            }
                                        }
                                    });
                                }
                            });
                    });
            });
    };

    $scope.print = function() {
        window.print();
    };

    $scope.reset = function() {
        $ngBootbox.confirm("Are you sure want to clear all the saved data?")
            .then(function() {
                crewRouteFactory.deleteWorkOrder($scope.route_id)
                    .success(function(response) {
                        if (response.success) {
                            init();
                            toastr.success('The work order info has been clear');
                        } else {
                            var msg = response.message || 'An error occurred while saving estimate';
                            toastr.error(msg);
                        }
                    });
            });
    };

    var prepareDataForSave = function() {
        var data = {};
        data.route_id = $scope.route_id;
        data.equipment_list = $scope.work_order.equipment_list;
        data.start_time = $scope.work_order.start_time;
        var estimatesData = [];
        for (var i = 0; i < $scope.route.assignedEstimates.length; i++) {
            var est = $scope.route.assignedEstimates[i];
            var estData = {
                id: est.id,
                eta: est.eta,
                lines: []
            };
            for (var j = 0; j < est.lines.length; j++) {
                var line = est.lines[j];
                if (line.visible && !line.is_empty) {
                    estData.lines.push({
                        id: line.line_id, // NOTE: use line_id, not id in DB
                        content: line.worker_order_line
                    });
                }
            }
            estimatesData.push(estData);
        }
        data.estimates_data = estimatesData;
        return data;
    };

    $scope.save = function() {
        crewRouteFactory.saveWorkOrder(prepareDataForSave())
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $scope.work_order.is_saved = true;
                } else {
                    toastr.error(response.message);
                }
            });
    };

    init();
}
