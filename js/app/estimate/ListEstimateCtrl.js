angular
    .module('Erp')
    .controller(
        'ListEstimateCtrl',
        [
            '$scope',
            '$rootScope',
            '$routeParams',
            '$location',
            '$timeout',
            'estimateFactory',
            'sharedData',
            'erpLocalStorage',
            'erpGeoLocation',
            'emailComposer',
            ListEstimateCtrl
        ]
    );

function ListEstimateCtrl(
        $scope,
        $rootScope,
        $routeParams,
        $location,
        $timeout,
        estimateFactory,
        sharedData,
        erpLocalStorage,
        erpGeoLocation,
        emailComposer) {

    $scope.setPageTitle('Estimates List');
    $scope.estimates = {};
    $scope.selectedStatus = '';
    $scope.sendMailData = {};
    $scope.filterParams = {
        keyword: '',
        status: ''
    };
    // Paginate
    $scope.total = 0;
    var currentPage = 1;
    if ('undefined' != typeof($routeParams.pageNumber)) {
        currentPage = $routeParams.pageNumber;
    }
    $scope.currentPage = currentPage;
    $scope.customers = [];
    $scope.isCheckingGeoLocation = false;

    var paginate = function() {
        var query = {
            page: $scope.currentPage,
            status: $scope.filterParams.status,
            keyword: $scope.filterParams.keyword
        };
        estimateFactory.list(query)
            .success(function(response) {
                $scope.estimates = response.data;
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
        $scope.searchEstimate();
    };

    $scope.searchEstimate = function() {
        $scope.currentPage = 1;
        paginate();
    };

    $scope.clearSearch = function() {
        $scope.filterParams = {
            keyword: '',
            status: ''
        };
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
            value: 'Accepted',
            label: 'Accepted'
        },
        {
            value: 'Routed',
            label: 'Routed' // NOTE: Quickbooks still shows Accepted
        },
        {
            value: 'Completed',
            label: 'Completed/WFI' // NOTE: Quickbooks still shows Accepted
        },

        {
            value: 'Closed',
            label: 'Closed'
        },
        {
            value: 'Rejected',
            label: 'Rejected'
        }
    ];

    $scope.startCheckGeolocation = function() {
        $scope.isCheckingGeoLocation = true;
        window.scrollTo(0, angular.element("#erp-content")[0].offsetTop - 100);
        var errorsCount = 0;
        var delay = 200;
        var nextItemIndex = 0;
        var geocoder = new google.maps.Geocoder();
        var itemsCount = $scope.estimates.length;
        for (var i = 0; i < itemsCount; i++) {
            if ('undefined' === typeof($scope.estimates[i].geolocation)) {
                $scope.estimates[i].geolocation = {};
            }
            $scope.estimates[i].geolocation.is_checked = false;
        }
        var checkEstimate = function(next) {
            var estimate = $scope.estimates[nextItemIndex];
            var fullAddress = estimate.job_address + ' ' + estimate.job_city + ' ' +
                estimate.job_state + ' ' + estimate.job_zip_code;
            estimate.geolocation.is_checking = true;
            $scope.$apply();
            geocoder.geocode( { address: fullAddress }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                    estimate.geolocation.ok = true;
                    estimate.geolocation.is_checking = false;
                    estimate.geolocation.is_checked = true;
                } else {
                    if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
                        nextItemIndex--;
                        delay += 200;
                    } else {
                        estimate.geolocation.is_checking = false;
                        estimate.geolocation.is_checked = true;
                        estimate.geolocation.ok = false;
                        errorsCount++;
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
                    checkEstimate(startCheck);
                    nextItemIndex++;
                }, delay);
            }
        };
        startCheck();
    };

    $scope.openSendMailModal = function(estimate) {
        $scope.showModal = true;
        $scope.sendMailData.doc_number = estimate.doc_number;
        $scope.sendMailData.id = estimate.id;
        $scope.sendMailData.to = estimate.email;
        $scope.sendMailData.subject = 'Estimate from ' + sharedData.companyInfo.name;
        var customerData = {
            family_name: estimate.billing_customer_family_name,
            given_name: estimate.billing_customer_given_name
        };
        $scope.sendMailData.body = emailComposer.getEstimateEmailContent(estimate, customerData);
        $scope.sendEmailForm.$setPristine();
        setTimeout(function() {
            angular.element('.estimate-mail-content')[0].focus();
        });
    };

    $scope.previewPdfEstimate = function(estimate) {
        $scope.showModalPdf = true;
        $scope.sendMailData.id = estimate.id;
    }

    $scope.sendMailEstimate = function() {
        $scope.showModal = false;
        estimateFactory.sendMail($scope.sendMailData)
            .success(function(response){
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            })
            .error(function() {
                toastr.error('An error has occurred while sending estimate.');
            });
    };
    paginate();
}
