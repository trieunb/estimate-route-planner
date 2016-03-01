angular
    .module('Erp')
    .controller(
        'AddEstimateCtrl',
        [
            '$scope',
            '$rootScope',
            '$http',
            '$timeout',
            '$routeParams',
            '$filter',
            '$location',
            'estimateFactory',
            'jobRequestFactory',
            'erpGeoLocation',
            '$ngBootbox',
            'sharedData',
            'erpLocalStorage',
            'erpOptions',
            AddEstimateCtrl
        ]
    );

function AddEstimateCtrl(
        $scope,
        $rootScope,
        $http,
        $timeout,
        $routeParams,
        $filter,
        $location,
        estimateFactory,
        jobRequestFactory,
        erpGeoLocation,
        $ngBootbox,
        sharedData,
        erpLocalStorage,
        erpOptions) {

    $scope.setPageTitle('New estimate');
    $scope.customers = [];
    $scope.jobCustomers = [];
    $scope.employees = [];
    $scope.estimate = {};
    $scope.estimate.lines = []; // Initial by 1 empty line
    $scope.estimate.total = 0.0;
    $scope.companyInfo = {};
    $scope.productServices = [];
    $scope.classes = [];
    angular.copy(sharedData.companyInfo, $scope.companyInfo);
    // Auto fill estimate footer
    $scope.estimate.estimate_footer = $scope.companyInfo.estimate_footer;
    $scope.estimate.disclaimer = $scope.companyInfo.disclaimer;
    $scope.estimate.sold_by_1 = sharedData.currentUserName;
    $scope.isShowModalSignature = false;
    $scope.estimateStatuses = erpOptions.estimateStatuses;
    $scope.estimatePriorities = erpOptions.estimatePriorities;

    $scope.estimate.txn_date = $filter('date')(new Date(), "yyyy-MM-dd");
    $scope.estimate.expiration_date =
        $filter('date')((new Date()).getTime() + (30 * 86400000), "yyyy-MM-dd");

    erpLocalStorage.getProductServices()
        .then(function(data) {
            angular.forEach(data, function(service) {
                if (service.active == 1) {
                    $scope.productServices.push(service);
                }
            });
        });

    $scope.soldBySelectConfig = {
        valueField: 'name',
        labelField: 'name',
        searchField: 'name',
        maxItems: 1
    };

    $scope.productServicesSelectConfig = {
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        maxItems: 1
    };

    // Load customers
    erpLocalStorage.getCustomers()
        .then(function(data) {
            $scope.customers = [];
            $scope.jobCustomers = [];
            angular.copy(data, $scope.customers);
            angular.copy(data, $scope.jobCustomers);
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

    var isTheSameCustomer = function() {
        return $scope.estimate.customer_id == $scope.estimate.job_customer_id;
    };

    /**
     * When a new customer has been created by bill customers dropdown
     */
    $scope.onBillCustomerCreated = function() {
        // Update job customers dropdown
        erpLocalStorage.getCustomers()
            .then(function(data) {
                $scope.jobCustomers = [];
                angular.copy(data, $scope.jobCustomers);
            });
    };

    // When the current customer's profile has been updated in the modal
    // And 'Update Form' is checked
    $scope.onBillCustomerUpdate = function() {
        resetBillCustomer();
        erpLocalStorage.getCustomers()
            .then(function(data) {
                $scope.jobCustomers = [];
                angular.copy(data, $scope.jobCustomers);
            });
        if (isTheSameCustomer()) {
            resetJobCustomer();
        }
    };

    // Handler customer change to populate fields
    $scope.onBillCustomerChange = function() {
        resetBillCustomer();
    };

    /**
     * When a new customer has been created by job customers dropdown
     */
    $scope.onJobCustomerCreated = function() {
        // Update billing customers dropdown
        erpLocalStorage.getCustomers()
            .then(function(data) {
                $scope.customers = [];
                angular.copy(data, $scope.customers);
            });
    };

    $scope.onJobCustomerUpdate = function() {
        resetJobCustomer();
        erpLocalStorage.getCustomers()
            .then(function(data) {
                $scope.customers = [];
                angular.copy(data, $scope.customers);
            });
        if (isTheSameCustomer()) {
            resetBillCustomer();
        }
    };

    $scope.onJobCustomerChange = function() {
        resetJobCustomer();
    };

    var resetBillCustomer = function() {
        if ('undefined' !== typeof($scope.estimate.customer_id)) {
            erpLocalStorage.getCustomers()
                .then(function(customers) {
                    for (var i = 0; i < customers.length; i++) {
                        if (customers[i].id == $scope.estimate.customer_id) {
                            var cus = customers[i];
                            $scope.estimate.bill_address = cus.bill_address;
                            $scope.estimate.bill_city = cus.bill_city;
                            $scope.estimate.bill_state = cus.bill_state;
                            $scope.estimate.bill_zip_code = cus.bill_zip_code;
                            $scope.estimate.bill_country = cus.bill_country;
                            $scope.estimate.primary_phone_number = cus.primary_phone_number;
                            $scope.estimate.mobile_phone_number = cus.mobile_phone_number;
                            $scope.estimate.email = cus.email;
                            $scope.estimate.bill_company_name = cus.company_name;
                            break;
                        }
                    }
                });
        }
    };

    var resetJobCustomer = function() {
        if ('undefined' !== typeof($scope.estimate.job_customer_id)) {
            erpLocalStorage.getCustomers()
                .then(function(customers) {
                    for (var i = 0; i < customers.length; i++) {
                        if (customers[i].id == $scope.estimate.job_customer_id) {
                            var cus = customers[i];
                            $scope.estimate.job_address = cus.ship_address;
                            $scope.estimate.job_city = cus.ship_city;
                            $scope.estimate.job_state = cus.ship_state;
                            $scope.estimate.job_zip_code = cus.ship_zip_code;
                            $scope.estimate.job_country = cus.ship_country;
                            $scope.estimate.job_company_name = cus.company_name;
                            break;
                        }
                    }
                });
        }
    };

    $scope.lineItemsDragListeners = {
        accept: function (sourceItemHandleScope, destSortableScope) {
            return true; //override to determine drag is allowed or not. default is true.
        },
        itemMoved: function (event) {
        },
        orderChanged: function(event) {
            $scope.reorderLineNum();
        }
    };

    $scope.addLine = function() {
        $scope.estimate.lines.push({
            product_service_id: null,
            line_id: null,
            qty: null,
            rate: null,
            total: null
        });
        $scope.updateTotal();
    };

    $scope.removeLine = function(line) {
        $scope.estimate.lines.splice($scope.estimate.lines.indexOf(line), 1);
        $scope.reorderLineNum();
        $scope.updateTotal();
    };

    $scope.removeAllLines = function() {
        $ngBootbox.confirm('Are you sure want to remove all the lines?')
            .then(
                function() {
                    $scope.estimate.lines = [];
                    $scope.updateTotal();
                }
            );
    };

    // Use product service description as line description
    $scope.changeLineProductService = function(line) {
        angular.forEach($scope.productServices, function(pd) {
            if (line.product_service_id == pd.id) {
                line.description = pd.description;
                line.rate = parseFloat(pd.rate);
                if (!line.qty) {
                    line.qty = 1;
                }
                return;
            }
        });
        $scope.updateTotal();
    };

    // Re-assign line num to lines after change the order
    $scope.reorderLineNum = function() {
        angular.forEach($scope.estimate.lines, function(line) {
            line.line_num = $scope.estimate.lines.indexOf(line) + 1;
        });
    };

    $scope.updateTotal = function() {
        var total = 0.0;
        if ($scope.estimate.lines.length > 0) {
            angular.forEach($scope.estimate.lines, function(line) {
                var rate = 0;
                var qty = 0;
                if (line.qty) {
                    qty = parseInt(line.qty);
                }
                if (line.rate) {
                    rate = parseFloat(line.rate);
                }
                if (line.product_service_id) {
                    var lineTotal = rate * qty;
                    line.total = lineTotal;
                    total += lineTotal;
                }
            });
        }
        $scope.estimate.total = parseFloat(total.toFixed(2));
    };

    // Check empty lines
    var isEmptyLines = function() {
        return $scope.estimate.lines.length === 0;
    };

    $scope.submitForm = function(print) {
        if (isEmptyLines()) {
            toastr.error('You must fill out at least one split line.');
        } else {
            erpGeoLocation.resolve(getJobFullAddress())
                .then(
                    function(result) {
                        $scope.estimate.job_lat = result.lat();
                        $scope.estimate.job_lng = result.lng();
                        var estimate = {};
                        angular.copy($scope.estimate, estimate);
                        if (estimate.txn_date) {
                            estimate.txn_date = ($filter('date')(estimate.txn_date, "yyyy-MM-dd"));
                        }
                        if (estimate.expiration_date) {
                            estimate.expiration_date = ($filter('date')(estimate.expiration_date, "yyyy-MM-dd"));
                        }
                        if (estimate.date_of_signature) {
                            estimate.date_of_signature = ($filter('date')(estimate.date_of_signature, "yyyy-MM-dd"));
                        }
                        // Get base64 of customer signature
                        if ($scope.signatureEncoded) {
                            estimate.customer_signature_encoded = $scope.signatureEncoded;
                        }
                        estimateFactory.save(estimate)
                            .success(function(response) {
                                if (response.success) {
                                    toastr.success(response.message);
                                    $location.path('/edit-estimate/' + response.data.id);
                                    if (print) {
                                        window.open(ERPApp.baseAPIPath + '&_do=printEstimate&id=' + response.data.id, '_blank');
                                    }
                                } else {
                                    var msg = response.message || 'An error occurred while saving estimate';
                                    toastr.error(msg);
                                }
                            })
                            .error(function() {
                                toastr.error('An error occurred while saving estimate');
                            });
                    },
                    function() {
                        toastr.error('Could not find geo location of job address!');
                    }
                );
        }
    };

    $scope.showSignatureBox = function() {
        $scope.isShowModalSignature = true;
    };

    $scope.onSaveSignature = function(signature) {
        $scope.signatureEncoded = signature;
    };

    var getJobFullAddress = function() {
        return $scope.estimate.job_address + ' ' +
            $scope.estimate.job_city + ' ' +
            $scope.estimate.job_state + ' ' +
            $scope.estimate.job_zip_code;
    };

    // Populate the form by job request info
    $timeout(function() {
        var jobRequestId = $routeParams.ref;
        if ('undefined' !== typeof(jobRequestId)) {
            jobRequestFactory.show(jobRequestId)
                .success(function(jobRequestData) {
                    $scope.estimate.customer_id = jobRequestData.customer_id;
                    $scope.estimate.job_customer_id = jobRequestData.customer_id;
                    // Set to customer billing address
                    resetBillCustomer();
                    $timeout(function() {
                        $scope.estimate.job_address = jobRequestData.address;
                        $scope.estimate.job_city = jobRequestData.city;
                        $scope.estimate.job_state = jobRequestData.state;
                        $scope.estimate.job_zip_code = jobRequestData.zip_code;
                        $scope.estimate.job_country = jobRequestData.country;
                        $scope.estimate.email = jobRequestData.email;
                        $scope.estimate.mobile_phone_number = jobRequestData.mobile_phone_number;
                        $scope.estimate.primary_phone_number = jobRequestData.primary_phone_number;
                        $scope.estimate.class_id = jobRequestData.class_id;
                        if (jobRequestData.estimator_id) {
                            for(var i = 0; i < $scope.employees.length; i++) {
                                if ($scope.employees[i].id == jobRequestData.estimator_id) {
                                    $scope.estimate.sold_by_1 = $scope.employees[i].name;
                                    break;
                                }
                            }
                        }
                    }, 300);
                });
        }
    }, 200);
}
