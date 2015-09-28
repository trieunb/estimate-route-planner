 angular.module('Erp')
    .controller('ListReferralRouteCtrl', [
        '$scope', '$rootScope', 'referralRouteFactory', '$ngBootbox', ListReferralRouteCtrl
    ]);

function ListReferralRouteCtrl($scope, $rootScope, referralRouteFactory, $ngBootbox) {
    $rootScope.pageTitle = 'Referral routes list';
    $rootScope.isBusy = true;
    $scope.referralRoutes = [];

    referralRouteFactory.all()
        .success(function(response) {
            $scope.referralRoutes = response;
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
                    referralRouteFactory.update(data)
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
