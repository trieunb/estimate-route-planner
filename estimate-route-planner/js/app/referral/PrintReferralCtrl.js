angular
    .module('Erp')
    .controller('PrintReferralCtrl', ['$scope', '$rootScope', 'referralFactory', '$routeParams','$location', '$filter', PrintReferralCtrl]);

function PrintReferralCtrl($scope, $rootScope, referralFactory, $routeParams, $location, $filter) {
    $rootScope.pageTitle = 'Referral';

    $scope.referral = $routeParams.id;

    referralFactory.show($scope.referral)
        .success(function(response) {
            var referral = response;
            referral.date_requested = new Date(referral.date_requested);
            referral.date_service = new Date(referral.date_service);
            $scope.referral = referral;
        });
    
    referralFactory.print($scope.referral)
        .success(function(response) {
            console.log(response);
        });
    
}