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
                    if ($scope.referral.customer_id == 0) {
                        // To force reload customer list
                        erpLocalStorage.clearCustomers();
                    }
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
