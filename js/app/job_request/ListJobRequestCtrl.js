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
    $scope.filterParams = {
        keyword: '',
        status: ''
    };
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
            _do: 'getEstimates',
            page: $scope.currentPage,
            status: $scope.filterParams.status,
            keyword: $scope.filterParams.keyword
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

    $scope.changeFilterStatus = function() {
        // Reset page to 1
        $scope.currentPage = 1;
        paginate();
    };

    $scope.onSelectCustomer = function(customer) {
        $scope.filterParams.keyword = customer.display_name;
        $scope.searchReferral();
    };

    $scope.searchReferral = function() {
        $scope.currentPage = 1;
        paginate();
    };

    $scope.clearSearch = function() {
        $scope.filterParams = {
            keyword: '',
            status: ''
        };
        paginate();
    };
    $scope.filterStatuses = [
        {
            value: '',
            label: 'All'
        },
        {
            value: 'Pending',
            label: 'Pending'
        },
        {
            value: 'Assigned',
            label: 'Assigned'
        },
        {
            value: 'Completed',
            label: 'Completed'
        }
    ];

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
        window.scrollTo(0, angular.element("#erp-content")[0].offsetTop - 100);
        var errorsCount = 0;
        var delay = 200;
        var nextItemIndex = 0;
        var geocoder = new google.maps.Geocoder();
        var itemsCount = $scope.referrals.length;
        for (var i = 0; i < itemsCount; i++) {
            if ('undefined' === typeof($scope.referrals[i].geolocation)) {
                $scope.referrals[i].geolocation = {};
            }
            $scope.referrals[i].geolocation.is_checked = false;
        }
        var checkReferral = function(next) {
            var ref = $scope.referrals[nextItemIndex];
            var fullAddress = ref.address + ' ' + ref.city + ' ' + ref.state + ' ' + ref.zip_code;
            ref.geolocation.is_checking = true;
            $scope.$apply();
            geocoder.geocode( { address: fullAddress }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                    ref.geolocation.ok = true;
                    ref.geolocation.is_checking = false;
                    ref.geolocation.is_checked = true;
                } else {
                    if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                        nextItemIndex--;
                        delay += 200;
                    } else {
                        ref.geolocation.is_checking = false;
                        ref.geolocation.is_checked = true;
                        ref.geolocation.ok = false;
                        errorsCount += 1;
                    }
                }
                $scope.$apply();
                if (nextItemIndex == itemsCount) {
                    $scope.isCheckingGeoLocation = false;
                    $timeout(function() {
                        if (errorsCount > 0) {
                            toastr.error("Found " + errorsCount + ' items has geolocation issue!');
                        } else {
                            toastr.success("No any geolocation issues found");
                        }
                    }, 500);
                }
                next();
            });
        };

        var startCheck = function() {
            if (nextItemIndex < itemsCount) {
                setTimeout(function() {
                    checkReferral(startCheck);
                    nextItemIndex++;
                }, delay);
            }
        };
        startCheck();
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
