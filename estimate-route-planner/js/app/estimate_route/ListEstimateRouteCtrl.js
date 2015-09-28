 angular.module('Erp')
    .controller('ListEstimateRouteCtrl', [
        '$scope', '$rootScope', 'estimateRouteFactory', '$ngBootbox', ListEstimateRouteCtrl]);

function ListEstimateRouteCtrl($scope, $rootScope, estimateRouteFactory, $ngBootbox) {
    $rootScope.pageTitle = 'Estimate routes list';
    $rootScope.isBusy = true;
    $scope.estimateRoutes = [];

    estimateRouteFactory.all()
        .success(function(response) {
            $scope.estimateRoutes = response;
        })
        .then(function() {
            $rootScope.isBusy = false;
        });

    $scope.saveRouteStatus = function(route) {
        $ngBootbox.confirm("Do want to save this route?")
            .then(
                function() {
                    $rootScope.isBusy = true;
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
                        })
                        .then(function() {
                            $rootScope.isBusy = false;
                        });
                },
                function() {
                    route.new_status = route.status;
                }
            );
    };
}
