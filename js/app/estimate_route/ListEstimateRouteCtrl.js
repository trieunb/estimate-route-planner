 angular.module('Erp')
    .controller(
        'ListEstimateRouteCtrl',
        [
            '$scope',
            '$rootScope',
            'estimateRouteFactory',
            '$ngBootbox',
            ListEstimateRouteCtrl
        ]
    );

function ListEstimateRouteCtrl($scope, $rootScope, estimateRouteFactory, $ngBootbox) {
    $scope.setPageTitle('Estimate routes list');
    $scope.estimateRoutes = [];

    estimateRouteFactory.all()
        .success(function(response) {
            $scope.estimateRoutes = response;
        });

    $scope.saveRouteStatus = function(route) {
        $ngBootbox.confirm("Do want to save this route?")
            .then(
                function() {
                    var data = {};
                    data.id = route.id;
                    data.status = route.new_status;
                    data.title = route.title;
                    estimateRouteFactory.update(data)
                        .success(function(response) {
                            route.status = route.new_status;
                            if (response.success) {
                                toastr['success'](response.message);
                            } else {
                                var errorMsg = response.message || 'An error has occurred while saving route';
                                toastr['error'](errorMsg);
                            }
                        });
                },
                function() {
                    route.new_status = route.status;
                }
            );
    };
}
