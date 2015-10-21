angular
    .module('Erp')
    .controller(
        'EditJobRequestCtrl',
        [
            '$scope',
            '$rootScope',
            'jobRequestFactory',
            'erpLocalStorage',
            'sharedData',
            '$routeParams',
            '$filter',
            '$window',
            'erpGeoLocation',
            EditJobRequestCtrl
        ]
    );

function EditJobRequestCtrl(
    $scope,
    $rootScope,
    jobRequestFactory,
    erpLocalStorage,
    sharedData,
    $routeParams,
    $filter,
    $window,
    erpGeoLocation) {
    $scope.setPageTitle('Edit Job Request');
    $scope.companyInfo = {};
    $scope.customers = [];
    $scope.employees = [];
    angular.copy(sharedData.companyInfo, $scope.companyInfo);

    $scope.employeesSelectConfig = {
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        maxItems: 1
    };

    var init = function() {
        jobRequestFactory.show($routeParams.id)
            .success(function(response) {
                $scope.referral = response;
                // Load customers list
                erpLocalStorage.getCustomers()
                    .then(function(data) {
                        var customers = data;
                        if ($scope.referral.customer_active == '0') {
                            customers.push({
                                id: $scope.referral.customer_id,
                                display_name: $scope.referral.customer_display_name,
                                order: customers.length
                            });
                        }
                        angular.copy(customers, $scope.customers);
                    });

                // Load employees
                erpLocalStorage.getEmployees()
                    .then(function(data) {
                        $scope.employees = [];
                        angular.copy(data, $scope.employees);
                    });
            });
    };

    $scope.onAddCustomer = function(input) {
        $scope.referral.customer_display_name = input;
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
            erpGeoLocation.resolve(getFullAddress())
                .then(
                    function(result) {
                        $scope.referral.lat = result.lat();
                        $scope.referral.lng = result.lng();
                        doSubmit();
                    },
                    function() {
                        toastr.error('Could not find geo location. Please check the address!');
                    }
                );
        } else {
            doSubmit();
        }
    };

    function doSubmit() {
        var referral = {};
        angular.copy($scope.referral, referral);
        referral.date_service = ($filter('date')(referral.date_service, "yyyy-MM-dd"));
        referral.date_requested = ($filter('date')(referral.date_requested, "yyyy-MM-dd"));
        jobRequestFactory.update(referral)
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $scope.referralForm.$setPristine();
                    // Reload to get refresh customer
                    if ($scope.referral.customer_id == 0) {
                        $window.location.reload();
                    }
                } else {
                    var msg = response.message || 'An error occurred while saving job request';
                    toastr.error(msg);
                }
            });
    }

    function getFullAddress() {
        return $scope.referral.address + ' ' +
            $scope.referral.city + ' ' +
            $scope.referral.state + ' ' +
            $scope.referral.zip_code;
    }

    init();
}
