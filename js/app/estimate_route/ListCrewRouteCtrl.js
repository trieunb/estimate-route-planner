 angular.module('Erp')
    .controller(
        'ListCrewRouteCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            'crewRouteFactory',
            '$ngBootbox',
            ListCrewRouteCtrl
        ]
    );

function ListCrewRouteCtrl($scope, $rootScope, $routeParams, crewRouteFactory, $ngBootbox) {
    $scope.setPageTitle('Crew Routes List');
    $scope.estimateRoutes = [];
    $scope.filter = {};

    $scope.total = 0;
    var currentPage = 1;
    if ('undefined' != typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;
    var paginate = function() {
        var query = {
            _do: 'getEstimateRoutes',
            page: $scope.currentPage,
            keyword: $scope.filter.keyword
        };
        crewRouteFactory.all(query)
            .success(function(response) {
                $scope.estimateRoutes = response.routes;
                $scope.total = parseInt(response.total);
            });
    };

    $scope.pageChanged = function() {
        paginate();
    };

    $scope.searchRoute = function() {
        $scope.currentPage = 1;
        paginate();
    };

    $scope.clearSearch = function() {
        $scope.filter = {};
        paginate();
    };

    $scope.saveRouteStatus = function(route) {
        $ngBootbox.confirm("Do want to save this route?")
            .then(
                function() {
                    var data = {};
                    data.id = route.id;
                    data.status = route.new_status;
                    data.title = route.title;
                    crewRouteFactory.update(data)
                        .success(function(response) {
                            route.status = route.new_status;
                            if (response.success) {
                                toastr.success(response.message);
                            } else {
                                var errorMsg = response.message || 'An error has occurred while saving route';
                                toastr.error(errorMsg);
                            }
                        });
                },
                function() {
                    route.new_status = route.status;
                }
            );
    };
    paginate();
}
