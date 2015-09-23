angular
    .module('Erp')
    .controller('ListReferralCtrl', [
        '$scope',
        '$routeParams',
        'referralFactory',
        'referralRouteFactory',
        '$ngBootbox',
        ListReferralCtrl
    ]);

function ListReferralCtrl($scope, $routeParams, referralFactory, referralRouteFactory, $ngBootbox) {
    $scope.setPageTitle('List Referrals');
    $scope.referrals = {};
    $scope.date = new Date();
    $scope.referralRoutes = [];
    $scope.showAssignModal = false;

    referralFactory.list()
        .success(function(response) {
            $scope.referrals = response;
            referralRouteFactory.all()
                .success(function(response) {
                    $scope.referralRoutes = response;
                });
        });

    $scope.showModalUpdateStatus = function(referral) {
        $scope.currentReferral = {};
        $scope.currentReferral.id = referral.id;
        $scope.currentReferral.status = referral.new_status;
        if (referral.new_status == 'Assigned') {
            $scope.showAssignModal = true;
        } else {
            $ngBootbox.confirm("Do you want to save referral status?")
                .then(function() {
                    $scope.showAssignModal = false;
                    $scope.updateReferralStatus();
                    $scope.currentReferral = {};
                }, function() {
                    referral.new_status = referral.status;
                    $scope.currentReferral = {};
                    $scope.assignReferralForm.$setPristine();
                });
        }
    };

    $scope.cancelAssignReferral = function() {
        $scope.showAssignModal = false;
        angular.forEach($scope.referrals, function(referral) {
            if (referral.id == $scope.currentReferral.id) {
                referral.new_status = referral.status;
                return;
            }
        });
        $scope.currentReferral = {};
        $scope.assignReferralForm.$setPristine();
    };

    $scope.updateReferralStatus = function() {
        if ($scope.currentReferral) {
            $scope.showAssignModal = false;
            referralFactory.updateStatus($scope.currentReferral)
                .success(function(response) {

                })
                .then(function() {
                    $scope.currentReferral = {};
                    $scope.assignReferralForm.$setPristine();
                });
        }
    };
}
