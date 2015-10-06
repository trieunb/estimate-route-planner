 angular.module('Erp')
    .controller(
        'AddJobRequestCtrl',
        [
            '$scope',
            '$rootScope',
            'jobRequestFactory',
            'customerFactory',
            'sharedData',
            '$location',
            '$filter',
            AddJobRequestCtrl
        ]
    );

function AddJobRequestCtrl(
    $scope,
    $rootScope,
    jobRequestFactory,
    customerFactory,
    sharedData,
    $location,
    $filter) {

    $scope.setPageTitle('New Job Request');
    $scope.companyInfo = {};
    $scope.customers = [];
    angular.copy(sharedData.companyInfo, $scope.companyInfo);
    // Initial with default status
    $scope.referral = {
        status: 'Pending'
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
                if (cus.id === 0) {
                    $scope.customers.splice(index, 1);
                    return;
                }
            });
            $scope.customers.push(newCustomer);
            $scope.referral.customer_display_name = input;
            callback(newCustomer);
        },
        render: {
            option: function(item, escape) {
                var itemClass = 'option ';
                var itemText = item.display_name;
                if (null !== item.parent_id && item.parent_id !== '0') {
                    itemClass += 'sub ';
                    itemClass += 'sub-level-' + item.sub_level;
                    itemText += '<small> Sub-customer of <b>' + item.parent_display_name + '</b></small>';
                }
                return '<div class="' + itemClass + '">' + itemText + '</div>';
            }
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
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode( { address: getFullAddress() }, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                var referral = {};
                angular.copy($scope.referral, referral);
                var location = results[0].geometry.location;
                referral.lat = location.lat();
                referral.lng = location.lng();
                referral.date_service = ($filter('date')(referral.date_service, "yyyy-MM-dd"));
                referral.date_requested = ($filter('date')(referral.date_requested, "yyyy-MM-dd"));
                jobRequestFactory.save(referral)
                    .success(function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            if ($scope.referral.customer_id == 0) {
                                // To force reload customer list
                                $rootScope.customers = undefined;
                            }
                            $location.path('/edit-job-request/' + response.data.id);
                        } else {
                            var msg = response.message || 'An error occurred while saving job request';
                            toastr.error(msg);
                        }
                    });
            } else {
                toastr.error('Could not find geo location. Please check the address!');
            }
        });
    };

    function getFullAddress() {
        return $scope.referral.address + ' ' +
            $scope.referral.city + ' ' +
            $scope.referral.state + ' ' +
            $scope.referral.zip_code;
    }
}
