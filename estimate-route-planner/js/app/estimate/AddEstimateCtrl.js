angular
    .module('Erp')
    .controller('AddEstimateCtrl', [
        '$scope', '$rootScope', '$http', '$routeParams', '$filter', '$location',
        'customerFactory', 'estimateFactory', 'employeeFactory',
        '$ngBootbox', 'customerFactory', AddEstimateCtrl
    ]);

function AddEstimateCtrl($scope, $rootScope, $http, $routeParams, $filter,
    $location, customerFactory, estimateFactory, employeeFactory, $ngBootbox, customerFactory) {
    $rootScope.pageTitle = 'New estimate';
    $scope.customers = [];
    $scope.employees = [];
    $scope.estimate = {};
    $scope.estimate.lines = []; // Initial by 1 empty line
    $scope.estimate.total = 0.0;
    $scope.isBusy = true;

    $scope.customersSelectConfig = {
      create: function(input, callback) {
        var data = {display_name: input};
        $rootScope.isBusy = true;
        customerFactory.create(data)
            .success(function(response) {
                if (response.success) {
                    toastr['success'](response.message);
                    $scope.customers.push(response.data);
                    callback(response.data);
                } else {
                    toastr['error'](response.message);
                    callback({});
                }
            })
            .then(function() {
                $rootScope.isBusy = false;
            });
      },
      valueField: 'id',
      labelField: 'display_name',
      sortField: 'display_name',
      searchField: 'display_name',
      maxItems: 1
    };


    // Load customers list
    customerFactory.all()
        .success(function(response) {
            $scope.customers = response;
        })
        .then(function() {
            // Load employees list
            employeeFactory.all()
                .success(function(response) {
                    $scope.employees = response;
                })
                .then(function() {
                    $scope.isBusy = false;
                });
        });

    $scope.clearCustomerSignature = function() {
        var signaturePad =  $scope.signature_pad;
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
        $scope.estimate.lines.push({
            'product_service_id': null,
            'line_id': null,
            'qty': null,
            'rate': null
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
        angular.forEach($rootScope.productServices, function(pd) {
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
        if(newVal && ('undefined' != typeof(oldVal))) {
            angular.forEach($scope.customers, function(cus) {
                if(cus.id == newVal) {
                    $scope.estimate.bill_address = cus.bill_address;
                    $scope.estimate.bill_city = cus.bill_city;
                    $scope.estimate.bill_state = cus.bill_state;
                    $scope.estimate.bill_zip_code = cus.bill_zip_code;
                    $scope.estimate.primary_phone_number = cus.primary_phone_number;
                    $scope.estimate.alternate_phone_number = cus.alternate_phone_number;
                    $scope.estimate.email = cus.email;
                }
            });
        }
    });

    $scope.$watch('estimate.job_customer_id', function(newVal, oldVal) {
        if (newVal && ('undefined' != typeof(oldVal)) ) {
            angular.forEach($scope.customers, function(cus) {
                if(cus.id == newVal) {
                    $scope.estimate.job_address = cus.bill_address;
                    $scope.estimate.job_city = cus.bill_city;
                    $scope.estimate.job_state = cus.bill_state;
                    $scope.estimate.job_zip_code = cus.bill_zip_code;
                }
            });
        }
    });

    $scope.updateTotal = function() {
        var total = 0.0;
        if ($scope.estimate.lines.length > 0) {
            angular.forEach($scope.estimate.lines, function(line) {
                var rate = qty = 0;
                if (line.qty) {
                    qty = parseInt(line.qty);
                }
                if (line.rate) {
                    rate = parseFloat(line.rate);
                }
                total += rate * qty;
            });
        }
        $scope.estimate.total = parseFloat(total.toPrecision(3));
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
            toastr['error']('You must fill out at least one split line.');
        } else {
            $rootScope.isBusy = true;
            // Get geolocation from job info
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode( { "address": getJobFullAddress() }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                    var location = results[0].geometry.location;
                    $scope.estimate.job_lat = location.lat();
                    $scope.estimate.job_lng = location.lng();
                    var estimate = {};
                    angular.copy($scope.estimate, estimate);
                    if (estimate.txn_date) {
                        estimate.txn_date = ($filter('date')(estimate.txn_date, "yyyy-MM-dd"));
                    }
                    if (estimate.due_date) {
                        estimate.due_date = ($filter('date')(estimate.due_date, "yyyy-MM-dd"));
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
                                toastr['success'](response.message);
                                $location.path('/edit-estimate/' + response.data.id);
                            } else {
                                var msg = response.message || 'An error occurred while saving estimate';
                                toastr['error'](msg);
                            }
                            $rootScope.isBusy = false;
                        })
                        .then(function() {
                            $rootScope.isBusy = false;
                        });
                } else {
                    toastr['error']('Could not find geo location of job info. Please check the job address!');
                    $rootScope.isBusy = false;
                }
            });
        }
    };

    var getJobFullAddress = function() {
        return $scope.estimate.job_address + ' '
            + $scope.estimate.job_city + ' '
            + $scope.estimate.job_state + ' '
            + $scope.estimate.job_zip_code;
    };
}
