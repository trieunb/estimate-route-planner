angular
    .module('Erp')
    .controller('ListJobRequestCtrl', [
        '$scope',
        '$routeParams',
        'jobRequestFactory',
        'estimateRouteFactory',
        'erpLocalStorage',
        'erpGeoLocation',
        'erpOptions',
        '$ngBootbox',
        '$timeout',
        ListJobRequestCtrl
    ]);

function ListJobRequestCtrl(
    $scope,
    $routeParams,
    jobRequestFactory,
    estimateRouteFactory,
    erpLocalStorage,
    erpGeoLocation,
    erpOptions,
    $ngBootbox,
    $timeout) {

    $scope.setPageTitle('Job Requests List');
    $scope.referrals = {};
    $scope.date = new Date();
    $scope.routes = [];
    $scope.showAssignModal = false;
    $scope.filter = {};
    $scope.total = 0;
    $scope.referralStatuses = erpOptions.referralStatuses;

    $scope.isCheckingGeoLocation = false;

    var currentPage = 1;
    if ('undefined' !== typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;

    // Load estimate route for quick assigned
    estimateRouteFactory.all()
        .success(function(responseData) {
            angular.forEach(responseData, function(route) {
                route.label = route.title + ' ' + '( ' + route.status + ' )';
                $scope.routes.push(route);
            });
        });

    // Load employees
    erpLocalStorage.getEmployees()
        .then(function(data) {
            $scope.employees = [];
            angular.copy(data, $scope.employees);
        });

    var paginate = function() {
        var query = {
            _do: 'getReferrals',
            page: $scope.currentPage,
            keyword: $scope.filter.keyword
        };
        jobRequestFactory.list(query)
            .success(function(response) {
                $scope.referrals = response.data;
                $scope.total = parseInt(response.total);
            });
    };

    $scope.pageChanged = function() {
        paginate();
    };

    $scope.onSelectCustomer = function(customer) {
        $scope.filter.keyword = customer.display_name;
        $scope.searchReferral();
    };

    $scope.searchReferral = function() {
        $scope.currentPage = 1;
        paginate();
    };

    $scope.clearSearch = function() {
        $scope.filter = {};
        paginate();
    };

    $scope.showModalUpdateStatus = function(referral) {
        $scope.currentReferral = {};
        $scope.currentReferral.id = referral.id;
        $scope.currentReferral.status = referral.new_status;
        if (referral.new_status == 'Assigned') {
            $scope.showAssignModal = true;
        } else {
            $ngBootbox.confirm("Do you want to save job request status?")
                .then(function() {
                    $scope.showAssignModal = false;
                    $scope.updateReferralStatus();
                    $scope.currentReferral = {};
                }, function() {
                    referral.new_status = referral.status;
                    $scope.currentReferral = {};
                    $scope.assignReferralForm.$setPristine();
                });
        }
    };

    $scope.cancelAssignReferral = function() {
        $scope.showAssignModal = false;
        // Reset new status value
        angular.forEach($scope.referrals, function(referral) {
            if (referral.id == $scope.currentReferral.id) {
                referral.new_status = referral.status;
                return;
            }
        });
        $scope.currentReferral = {};
        $scope.assignReferralForm.$setPristine();
    };

    $scope.startCheckGeolocation = function() {
        $scope.isCheckingGeoLocation = true;
        var length = $scope.referrals.length;
        angular.forEach($scope.referrals, function(referral) {
            referral.geolocation = {
                is_checking: true,
                is_checked: false
            };
            if (referral.address.length === 0) {
                referral.geolocation.ok = false;
                referral.geolocation.is_checking = false;
                referral.geolocation.is_checked = true;
            } else {
                var fullAddress = referral.address + ' ' + referral.city + ' ' +
                    referral.state + ' ' + referral.zip_code;

                $timeout(function() {
                    erpGeoLocation.resolve(fullAddress)
                        .then(
                            function(result) {
                                referral.geolocation.ok = true;
                            },
                            function() {
                                referral.geolocation.ok = false;
                                toastr.error("An error has occurred! You might had exceed the Google Maps API usage limits. Please try again in few seconds.");
                            }
                        );
                    referral.geolocation.is_checking = false;
                    referral.geolocation.is_checked = true;
                }, 100);
            }
        });

        $scope.isCheckingGeoLocation = false;
    };

    $scope.updateReferralStatus = function() {
        if ($scope.currentReferral) {
            $scope.showAssignModal = false;
            var newStatus = $scope.currentReferral.status;
            var updateReferralId = $scope.currentReferral.id;
            jobRequestFactory.updateStatus($scope.currentReferral)
                .success(function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // Remove completed referral from list
                        if (newStatus === 'Completed') {
                            angular.forEach($scope.referrals, function(referral, index) {
                                if (referral.id == updateReferralId) {
                                    $scope.referrals.splice(index, 1);
                                    return;
                                }
                            });
                        }
                    } else {
                        var errorMsg = response.message ||
                            'An error has occurred while saving route';
                        toastr.error(errorMsg);
                    }
                })
                .then(function() {
                    $scope.currentReferral = {};
                    $scope.assignReferralForm.$setPristine();
                });
        }
    };
    paginate();
}
