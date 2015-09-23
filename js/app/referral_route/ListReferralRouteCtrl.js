 angular.module('Erp')
    .controller(
        'ListReferralRouteCtrl',
        [
            '$scope',
            '$rootScope',
            'referralRouteFactory',
            '$ngBootbox',
            ListReferralRouteCtrl
        ]
    );

function ListReferralRouteCtrl($scope, $rootScope, referralRouteFactory, $ngBootbox) {
    $scope.setPageTitle('Referral routes list');
    $scope.referralRoutes = [];

    referralRouteFactory.all()
        .success(function(response) {
            $scope.referralRoutes = response;
        });

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
}
