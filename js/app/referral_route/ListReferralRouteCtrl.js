 angular.module('Erp')
    .controller(
        'ListReferralRouteCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            'referralRouteFactory',
            '$ngBootbox',
            ListReferralRouteCtrl
        ]
    );

function ListReferralRouteCtrl($scope, $rootScope, $routeParams, referralRouteFactory, $ngBootbox) {
    $scope.setPageTitle('Referral routes list');
    $scope.referralRoutes = [];

    // referralRouteFactory.all()
    //     .success(function(response) {
    //         $scope.referralRoutes = response.routes;
    //     });

    $scope.saveRouteStatus = function(route) {
        $ngBootbox.confirm("Do want to save this route?")
            .then(
                function() {
                    var data = {};
                    data.id = route.id;
                    data.status = route.new_status;
                    data.title = route.title;
                    referralRouteFactory.update(data)
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
    //pagination
    $scope.total = 0;
    var currentPage = 1;
    if ('undefined' != typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;
    var paginate = function() {
        var query = {
            _do: 'getReferralRoutes',
            page: $scope.currentPage,
            keyword: $scope.referralRoutes.title
        }
        referralRouteFactory.all(query)
            .success(function(response) {
                $scope.referralRoutes = response.routes;
                $scope.referralRoutes.title = response.keyword;
                $scope.total = parseInt(response.total);
            });
    };

    $scope.pageChanged = function() {
        paginate();
    };
    paginate();

    $scope.searchRoute = function() {
        $scope.currentPage = 1;
        paginate();
    };
}
