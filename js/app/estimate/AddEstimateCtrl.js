angular
    .module('Erp')
    .controller(
        'AddEstimateCtrl',
        [
            '$scope',
            '$rootScope',
            '$http',
            '$routeParams',
            '$filter',
            '$location',
            'customerFactory',
            'estimateFactory',
            'employeeFactory',
            'erpGeoLocation',
            '$ngBootbox',
            'sharedData',
            'erpLocalStorage',
            AddEstimateCtrl
        ]
    );

function AddEstimateCtrl(
        $scope,
        $rootScope,
        $http,
        $routeParams,
        $filter,
        $location,
        customerFactory,
        estimateFactory,
        employeeFactory,
        erpGeoLocation,
        $ngBootbox,
        sharedData,
        erpLocalStorage) {
    $scope.setPageTitle('New estimate');
    $scope.customers = [];
    $scope.jobCustomers = [];
    $scope.employees = [];
    $scope.estimate = {};
    $scope.estimate.lines = []; // Initial by 1 empty line
    $scope.estimate.total = 0.0;
    $scope.companyInfo = {};
    $scope.productServices = [];
    angular.copy(sharedData.companyInfo, $scope.companyInfo);
    // Auto fill estimate footer
    $scope.estimate.estimate_footer = $scope.companyInfo.estimate_footer;
    $scope.estimate.sold_by_1 = sharedData.currentUserName;
    // Filter active product services
    angular.forEach(sharedData.productServices, function(pd) {
        if (pd.active == 1) {
            $scope.productServices.push(pd);
        }
    });

    $scope.soldBySelectConfig = {
        valueField: 'name',
        labelField: 'name',
        searchField: 'name',
        maxItems: 1
    };

    // Load customers list
    erpLocalStorage.getCustomers()
        .then(function(data) {
            angular.copy(data, $scope.customers);
            angular.copy(data, $scope.jobCustomers);
        });
    // Load employees
    erpLocalStorage.getEmployees()
        .then(function(data) {
            angular.copy(data, $scope.employees);
            console.log('employees scope:', $scope.employees);
        });

    console.log('employees scope:', $scope.employees);
    // if (typeof($rootScope.employees) !== 'undefined') {
    //     angular.copy($rootScope.employees, $scope.employees);
    // } else {
    //     employeeFactory.all()
    //         .success(function(response) {
    //             $scope.employees = response;
    //             $rootScope.employees = [];
    //             angular.copy($scope.employees, $rootScope.employees);
    //         });
    // }

    $scope.onAddCustomer = function(input) {
        $scope.estimate.customer_display_name = input;
    };

    $scope.onAddJobCustomer = function(input) {
        $scope.estimate.job_customer_display_name = input;
    };

    $scope.clearCustomerSignature = function() {
        var signaturePad = $scope.signature_pad;
        signaturePad.clear();
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
        console.log('employees scope:', $scope.employees);
        $scope.estimate.lines.push({
            product_service_id: null,
            line_id: null,
            qty: 0,
            rate: 0,
            total: 0
        });
        $scope.updateTotal();
    };

    $scope.removeLine = function(line) {
        $scope.estimate.lines.splice($scope.estimate.lines.indexOf(line), 1);
        $scope.reorderLineNum();
        $scope.updateTotal();
    };

    $scope.removeAllLines = function() {
        $scope.estimate.lines = [];
        $scope.updateTotal();
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

    // What customer change to populate customer fields
    $scope.$watch('estimate.customer_id', function(newVal, oldVal) {
        if ($scope.estimateForm.$dirty && ('undefined' != typeof(newVal))) {
            angular.forEach($scope.customers, function(cus) {
                if (cus.id == newVal) {
                    if (newVal != 0) { // Keep entered info if new client
                        $scope.estimate.bill_address = cus.bill_address;
                        $scope.estimate.bill_city = cus.bill_city;
                        $scope.estimate.bill_state = cus.bill_state;
                        $scope.estimate.bill_zip_code = cus.bill_zip_code;
                        $scope.estimate.primary_phone_number = cus.primary_phone_number;
                        $scope.estimate.alternate_phone_number = cus.alternate_phone_number;
                        $scope.estimate.email = cus.email;
                    }
                    $scope.estimate.customer_display_name = cus.display_name;
                    return;
                }
            });
        }
    });

    $scope.$watch('estimate.job_customer_id', function(newVal, oldVal) {
        if ($scope.estimateForm.$dirty && ('undefined' != typeof(newVal))) {
            angular.forEach($scope.jobCustomers, function(cus) {
                if (cus.id == newVal) {
                    if (newVal != 0) { // Keep entered info if new client
                        $scope.estimate.job_address = cus.ship_address;
                        $scope.estimate.job_city = cus.ship_city;
                        $scope.estimate.job_state = cus.ship_state;
                        $scope.estimate.job_zip_code = cus.ship_zip_code;
                    }
                    $scope.estimate.job_customer_display_name = cus.display_name;
                    return;
                }
            });
        }
    });

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
                var lineTotal = rate * qty;
                total += lineTotal;
                line.total = lineTotal;
            });
        }
        $scope.estimate.total = parseFloat(total.toFixed(2));
    };

    // Check empty lines
    var isEmptyLines = function() {
        var isEmpty = true;
        angular.forEach($scope.estimate.lines, function(line) {
            if (line.product_service_id && line.rate && line.qty) {
                isEmpty = false;
            }
        });
        return isEmpty;
    };

    $scope.submitForm = function() {
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
                        var signaturePad =  $scope.signature_pad;
                        if (!signaturePad.isEmpty()) {
                            estimate.customer_signature_encoded = signaturePad.toDataURL();
                        }
                        estimateFactory.save(estimate)
                            .success(function(response) {
                                if (response.success) {
                                    toastr.success(response.message);
                                    if ($scope.estimate.customer_id == 0 ||
                                        $scope.estimate.job_customer_id == 0) {
                                        // To force reload customer list
                                        $rootScope.customers = undefined;
                                    }
                                    $location.path('/edit-estimate/' + response.data.id);
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

    var getJobFullAddress = function() {
        return $scope.estimate.job_address + ' ' +
            $scope.estimate.job_city + ' ' +
            $scope.estimate.job_state + ' ' +
            $scope.estimate.job_zip_code;
    };
}
