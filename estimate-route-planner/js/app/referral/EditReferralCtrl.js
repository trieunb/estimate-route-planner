angular
    .module('Erp')
    .controller('EditReferralCtrl', ['$scope', '$rootScope', 'referralFactory', '$routeParams','$location', '$filter', EditReferralCtrl]);

function EditReferralCtrl($scope, $rootScope, referralFactory, $routeParams, $location, $filter) {
    $rootScope.pageTitle = 'Edit Referral';
    $rootScope.isBusy = true;
    $scope.referral = $routeParams.id;

    referralFactory.show($scope.referral)
        .success(function(response) {
            var referral = response;
            //referral.date_requested = new Date(referral.date_requested);
            //referral.date_service = new Date(referral.date_service);
            $scope.referral = referral;
        })
        .then(function() {
            $rootScope.isBusy = false;
        });

    $scope.submitForm = function() {
        $rootScope.isBusy = true;
        // Check address files changed to regeolocation
        if ($scope.referralForm.address.$dirty 
                || $scope.referralForm.city.$dirty
                    || $scope.referralForm.state.$dirty
                        || $scope.referralForm.zip_code.$dirty
            ) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode( { "address": getFullAddress() }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                    var location = results[0].geometry.location;
                    $scope.referral.lat = location.lat();
                    $scope.referral.lng = location.lng();
                    doSubmit();
                } else {
                    toastr['error']('Could not find location. Please check the address!');
                    $rootScope.isBusy = false;
                }
            }); 
        } else {
            doSubmit();
        }
    };


    function getFullAddress() {
        return $scope.referral.address + ' '
            + $scope.referral.city + ' '
            + $scope.referral.state + ' '
            + $scope.referral.zip_code;
    };

    function doSubmit() {
        var referral = {};
        angular.copy($scope.referral, referral);        
        referral.date_service = ($filter('date')(referral.date_service, "yyyy-MM-dd"));
        referral.date_requested = ($filter('date')(referral.date_requested, "yyyy-MM-dd"));
        referralFactory.update(referral)
            .success(function(response) {
                if (response.success) {
                    toastr['success'](response.message);
                    $scope.referralForm.$setPristine();
                } else {
                    var msg = response.message || 'An error occurred while saving referral';
                    toastr['error'](msg);
                }                
            })
            .then(function() {
                $rootScope.isBusy = false;
            });
    }
}