angular
    .module('Erp')
    .controller(
        'EditReferralCtrl',
        [
            '$scope',
            '$rootScope',
            'referralFactory',
            'customerFactory',
            'sharedData',
            '$routeParams',
            '$filter',
            '$window',
            EditReferralCtrl
        ]
    );

function EditReferralCtrl(
    $scope,
    $rootScope,
    referralFactory,
    customerFactory,
    sharedData,
    $routeParams,
    $filter,
    $window) {
    $scope.setPageTitle('Edit Job Request');
    $scope.companyInfo = {};
    $scope.customers = [];
    angular.copy(sharedData.companyInfo, $scope.companyInfo);

    var init = function() {
        $scope.referral = $routeParams.id;
        referralFactory.show($scope.referral)
            .success(function(response) {
                $scope.referral = response;
            });
    };

    // Load customers list
    if (typeof($rootScope.customers) !== 'undefined') {
        angular.copy($rootScope.customers, $scope.customers);
    } else {
        customerFactory.all()
            .success(function(response) {
                $scope.customers = response;
                $rootScope.customers = [];
                angular.copy($scope.customers, $rootScope.customers);
            });
    }


        $scope.customersSelectConfig = {
            valueField: 'id',
            labelField: 'display_name',
            sortField: 'display_name',
            searchField: 'display_name',
            selectOnTab: true,
            maxItems: 1,
            maxOptions: 10000,
            create: function(input, callback) {
                var newCustomer = {
                    id: 0,
                    display_name: input
                };
                angular.forEach($scope.customers, function(cus, index) {
                    // Remove last new customer
                    if (cus.id == 0) {
                        $scope.customers.splice(index, 1);
                        return;
                    }
                });
                $scope.customers.push(newCustomer);
                $scope.referral.customer_display_name = input;
                callback(newCustomer);
            }
        };

        // What customer change to populate customer fields
        $scope.$watch('referral.customer_id', function(newVal, oldVal) {
            if ($scope.referralForm.$dirty && ('undefined' != typeof(newVal))) {
                angular.forEach($scope.customers, function(cus) {
                    if (cus.id == newVal) {
                        if (newVal != 0) { // Keep entered info if new client
                            $scope.referral.address = cus.ship_address;
                            $scope.referral.city = cus.ship_city;
                            $scope.referral.state = cus.ship_state;
                            $scope.referral.zip_code = cus.ship_zip_code;
                            $scope.referral.country = cus.ship_country;
                            $scope.referral.primary_phone_number = cus.primary_phone_number;
                            $scope.referral.email = cus.email;
                        }
                        $scope.referral.customer_display_name = cus.display_name;
                        return;
                    }
                });
            }
        });

    $scope.submitForm = function() {
        // Check address files changed to regeolocation
        if ($scope.referralForm.address.$dirty ||
                $scope.referralForm.city.$dirty ||
                $scope.referralForm.state.$dirty ||
                $scope.referralForm.zip_code.$dirty
            ) {
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode( { address: getFullAddress() }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                    var location = results[0].geometry.location;
                    $scope.referral.lat = location.lat();
                    $scope.referral.lng = location.lng();
                    doSubmit();
                } else {
                    toastr.error('Could not find geo location. Please check the address!');
                }
            });
        } else {
            doSubmit();
        }
    };

    function getFullAddress() {
        return $scope.referral.address + ' ' +
            $scope.referral.city + ' ' +
            $scope.referral.state + ' ' +
            $scope.referral.zip_code + ' ' +
            $scope.referral.country;
    }

    function doSubmit() {
        var referral = {};
        angular.copy($scope.referral, referral);
        referral.date_service = ($filter('date')(referral.date_service, "yyyy-MM-dd"));
        referral.date_requested = ($filter('date')(referral.date_requested, "yyyy-MM-dd"));
        referralFactory.update(referral)
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $scope.referralForm.$setPristine();
                    // Reload to get refresh customer
                    if ($scope.referral.customer_id == 0) {
                        $window.location.reload();
                    }
                } else {
                    var msg = response.message || 'An error occurred while saving referral';
                    toastr.error(msg);
                }
            });
    }

    init();
}
