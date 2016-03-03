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
            'erpOptions',
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
    erpGeoLocation,
    erpOptions) {

    $scope.setPageTitle('Edit Job Request');
    $scope.companyInfo = {};
    $scope.customers = [];
    $scope.employees = [];
    $scope.classes = [];
    $scope.jobPriorities = erpOptions.jobPriorities;
    angular.copy(sharedData.companyInfo, $scope.companyInfo);

    var init = function() {
        jobRequestFactory.show($routeParams.id)
            .success(function(response) {
                $scope.referral = response;
                $scope.referral.date_requested = new Date(response.date_requested);
                $scope.referral.date_service = new Date(response.date_service);
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

                // Load classes
                erpLocalStorage.getClasses()
                    .then(function(data) {
                        $scope.classes = [];
                        angular.copy(data, $scope.classes);
                    });
            });
    };

    // When the current customer's profile has been updated in the modal
    // and 'Update Form' is checked
    $scope.onCustomerUpdate = function() {
        resetCustomer();
    };

    var resetCustomer = function() {
        if ('undefined' !== typeof($scope.referral.customer_id)) {
            erpLocalStorage.getCustomers()
                .then(function(customers) {
                    angular.forEach(customers, function(cus) {
                        if (cus.id == $scope.referral.customer_id) {
                            $scope.referral.address = cus.ship_address;
                            $scope.referral.city = cus.ship_city;
                            $scope.referral.state = cus.ship_state;
                            $scope.referral.zip_code = cus.ship_zip_code;
                            $scope.referral.country = cus.ship_country;
                            $scope.referral.primary_phone_number = cus.primary_phone_number;
                            $scope.referral.mobile_phone_number = cus.mobile_phone_number;
                            $scope.referral.email = cus.email;
                            $scope.referral.company_name = cus.company_name;
                            return;
                        }
                    });
                });
        }
    };

    // Handler customer change to populate fields
    $scope.onCustomerChange = function() {
        resetCustomer();
    };

    $scope.submitForm = function() {
        // Check address files changed to re-geolocation
        if ($scope.referral.address && $scope.referral.address.length) {
            erpGeoLocation.resolve(getFullAddress())
                .then(
                    function(result) {
                        $scope.referral.lat = result.lat();
                        $scope.referral.lng = result.lng();
                        saveReferral();
                    },
                    function() {
                        toastr.error('Could not find geo location. Please check the address!');
                    }
                );
        } else {
            saveReferral();
        }
    };

    function saveReferral() {
        var referral = {};
        angular.copy($scope.referral, referral);
        referral.date_service = ($filter('date')(referral.date_service, "yyyy-MM-dd"));
        referral.date_requested = ($filter('date')(referral.date_requested, "yyyy-MM-dd"));
        jobRequestFactory.update(referral)
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $scope.referralForm.$setPristine();
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
