 angular.module('Erp')
    .controller('AddReferralCtrl', ['$scope', '$rootScope', 
        'referralFactory', '$location', '$filter', AddReferralCtrl]);

function AddReferralCtrl($scope, $rootScope, referralFactory, $location, $filter) {
    $rootScope.pageTitle = 'New referral';

    $scope.referral = {
        status: 'Pending'
    };

    $scope.submitForm = function() {
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode( { "address": getFullAddress() }, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                var referral = {};
                angular.copy($scope.referral, referral);
                var location = results[0].geometry.location;
                referral.lat = location.lat();
                referral.lng = location.lng();
                referral.date_service = ($filter('date')(referral.date_service, "yyyy-MM-dd"));
                referral.date_requested = ($filter('date')(referral.date_requested, "yyyy-MM-dd"));
                referralFactory.save(referral)
                    .success(function(response) {
                        if (response.success) {
                            toastr['success'](response.message);
                            $location.path('/edit-referral/' + response.data.id);
                        } else {
                            var msg = response.message || 'An error occurred while saving referral';
                            toastr['error'](msg);
                        }
                    });
            } else {
                toastr['error']('Could not find geo location. Please check the address!');
            }
        });
    };

    function getFullAddress() {
        return $scope.referral.address + ' '
            + $scope.referral.city + ' '
            + $scope.referral.state + ' '
            + $scope.referral.zip_code;
    };

}
