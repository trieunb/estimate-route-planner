angular
    .module('Erp')
    .controller('ListReferralCtrl', [
        '$scope',
        '$rootScope',
        '$routeParams',
        'referralFactory',
        'referralRouteFactory',
        '$ngBootbox',
         ListReferralCtrl
    ]);

function ListReferralCtrl($scope, $rootScope, $routeParams, referralFactory,
        referralRouteFactory, $ngBootbox) {
    $rootScope.pageTitle = 'List referrals';
    $rootScope.isBusy = true;
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
                })
                .then(function() {
                    $rootScope.isBusy = false;
                });
        })
        .then(function() {
            $rootScope.isBusy = false;
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
    };

    $scope.updateReferralStatus = function() {
        if ($scope.currentReferral) {
            $scope.showAssignModal = false;
            $rootScope.isBusy = true;
            referralFactory.updateStatus($scope.currentReferral)
                .success(function(response) {

                })
                .then(function() {
                    $rootScope.isBusy = false;
                    $scope.currentReferral = {};
                });
        }
    };
}
