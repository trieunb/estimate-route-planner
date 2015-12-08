 angular.module('Erp')
    .controller(
        'ListCrewRouteCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            'crewRouteFactory',
            'erpOptions',
            '$ngBootbox',
            ListCrewRouteCtrl
        ]
    );

function ListCrewRouteCtrl(
    $scope,
    $rootScope,
    $routeParams,
    crewRouteFactory,
    erpOptions,
    $ngBootbox) {

    $scope.setPageTitle('Crew Routes List');
    $scope.routes = [];
    $scope.filter = {};

    $scope.total = 0;
    var currentPage = 1;
    if ('undefined' != typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;

    var paginate = function() {
        var query = {
            _do: 'getCrewRoutes',
            page: $scope.currentPage,
            keyword: $scope.filter.keyword
        };
        crewRouteFactory.all(query)
            .success(function(response) {
                $scope.routes = response.routes;
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

    paginate();
}
