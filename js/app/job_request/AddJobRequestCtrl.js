 angular.module('Erp')
    .controller(
        'AddJobRequestCtrl',
        [
            '$scope',
            '$rootScope',
            'jobRequestFactory',
            'erpLocalStorage',
            'sharedData',
            '$location',
            '$filter',
            '$uibModal',
            'erpGeoLocation',
            AddJobRequestCtrl
        ]
    );

function AddJobRequestCtrl(
    $scope,
    $rootScope,
    jobRequestFactory,
    erpLocalStorage,
    sharedData,
    $location,
    $filter,
    $uibModal,
    erpGeoLocation) {

    $scope.setPageTitle('New Job Request');
    $scope.companyInfo = {};
    $scope.customers = [];
    $scope.employees = [];
    $scope.classes = [];
    angular.copy(sharedData.companyInfo, $scope.companyInfo);

    // Initial with default status
    $scope.referral = {
        status: 'Pending'
    };

    // Load customers list
    erpLocalStorage.getCustomers()
        .then(function(data) {
            $scope.customers = [];
            angular.copy(data, $scope.customers);
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

    // When the current customer's profile has been updated in the modal
    // And 'Update Form' is checked
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
        var referralData = {};
        angular.copy($scope.referral, referralData);
        referralData.date_service = $filter('date')(referralData.date_service, "yyyy-MM-dd");
        referralData.date_requested = $filter('date')(referralData.date_requested, "yyyy-MM-dd");
        jobRequestFactory.save(referralData)
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $location.path('/edit-job-request/' + response.data.id);
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
}
