 angular.module('Erp')
    .controller(
        'ListEstimateRouteCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            'estimateRouteFactory',
            '$ngBootbox',
            ListEstimateRouteCtrl
        ]
    );

function ListEstimateRouteCtrl(
    $scope,
    $rootScope,
    $routeParams,
    estimateRouteFactory,
    $ngBootbox) {

    $scope.setPageTitle('Estimate Routes List');
    $scope.routes = [];
    $scope.filter = {};

    // Pagination
    $scope.total = 0;
    var currentPage = 1;
    if ('undefined' !== typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;

    var paginate = function() {
        var query = {
            _do: 'filterEstimateRoutes',
            page: $scope.currentPage,
            keyword: $scope.filter.keyword
        };
        estimateRouteFactory.filter(query)
            .success(function(response) {
                $scope.routes = response.routes;
                $scope.total = parseInt(response.total);
            });
    };

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
                                toastr.success(response.message);
                            } else {
                                var errorMsg = response.message ||
                                    'An error has occurred while saving route';
                                toastr.error(errorMsg);
                            }
                        });
                },
                function() {
                    route.new_status = route.status;
                }
            );
    };

    $scope.pageChanged = function() {
        paginate();
    };

    $scope.clearSearch = function() {
        $scope.filter = {};
        paginate();
    };

    $scope.searchRoute = function() {
        $scope.currentPage = 1;
        paginate();
    };

    paginate();
}
