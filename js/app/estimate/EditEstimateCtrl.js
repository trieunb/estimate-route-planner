angular
    .module('Erp')
    .controller(
        'EditEstimateCtrl',
        [
            '$scope',
            '$rootScope',
            '$http',
            '$routeParams',
            '$filter',
            '$location',
            'estimateFactory',
            'erpLocalStorage',
            'erpGeoLocation',
            'attachmentUploader',
            'sharedData',
            'erpOptions',
            '$ngBootbox',
            '$window',
            EditEstimateCtrl
        ]
    );

function EditEstimateCtrl(
        $scope,
        $rootScope,
        $http,
        $routeParams,
        $filter,
        $location,
        estimateFactory,
        erpLocalStorage,
        erpGeoLocation,
        attachmentUploader,
        sharedData,
        erpOptions,
        $ngBootbox,
        $window) {

    $scope.setPageTitle('Estimate');
    $scope.customers = [];
    $scope.jobCustomers = [];
    $scope.employees = [];
    $scope.estimate = {};
    $scope.uploadProgress = 0;
    $scope.isChangedSignature = false;
    $scope.companyInfo = {};
    $scope.productServices = [];
    $scope.classes = [];

    angular.copy(sharedData.companyInfo, $scope.companyInfo);
    $scope.isShowModalSignature = false;
    $scope.estimateStatuses = erpOptions.estimateStatuses;

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

    erpLocalStorage.getProductServices()
        .then(function(data) {
            angular.forEach(data, function(service) {
                if (service.active == 1) {
                    $scope.productServices.push(service);
                }
            });
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

    // Get estimate data
    estimateFactory.show($routeParams.id)
        .success(function(response) {
            var estimate = response;
            if (estimate.doc_number) {
                $rootScope.pageTitle = 'Estimate #' + estimate.doc_number;
            } else {
                $rootScope.pageTitle = 'Edit estimate';
            }

            // Assign line_num for the empty line as length of estimate lines
            var lineProductServiceIds = [];
            angular.forEach(estimate.lines, function(line) {
                if (line.line_num === null) {
                    line.line_num = estimate.lines.length;
                } else {
                    line.line_num = parseInt(line.line_num);
                }

                if (line.rate !== null) {
                    line.rate = parseFloat(line.rate);
                }

                if (line.qty !== null) {
                    line.qty = parseInt(line.qty);
                }
                var lineTotal = line.qty * line.rate;

                if (line.product_service_id) {
                    line.total = parseFloat(lineTotal.toFixed(2));
                    lineProductServiceIds.push(line.product_service_id);
                }
            });

            // Product and services list

            erpLocalStorage.getProductServices()
                .then(function(data) {
                    angular.forEach(data, function(pd) {
                        // Check for add inactive products to the dropdown
                        // if a line has assigned with them
                        if ((pd.active == 1) ||
                            (lineProductServiceIds.indexOf(pd.id) !== -1)) {
                            $scope.productServices.push(pd);
                        }
                    });
                });

            // Order lines by line_num
            estimate.lines = $filter('orderBy')(estimate.lines, 'line_num', false);

            // Load signature canvas
            if (estimate.customer_signature) {
                $scope.signatureEncoded =
                    $rootScope.baseERPPluginUrl + estimate.customer_signature;
            }
            $scope.estimate = estimate;
            $scope.updateTotal();

            // Load customers
            erpLocalStorage.getCustomers()
                .then(function(data) {
                    $scope.customers = [];
                    $scope.jobCustomers = [];
                    angular.copy(data, $scope.customers);
                    if ($scope.estimate.customer_active === '0') {
                        $scope.customers.push({
                            id: $scope.estimate.customer_id,
                            display_name: $scope.estimate.customer_display_name,
                            order: $scope.customers.length
                        });
                    }
                    angular.copy(data, $scope.jobCustomers);
                    if ($scope.estimate.job_customer_active === '0') {
                        $scope.jobCustomers.push({
                            id: $scope.estimate.job_customer_id,
                            display_name: $scope.estimate.job_customer_display_name,
                            order: $scope.jobCustomers.length
                        });
                    }
                });
        });

    $scope.dropzoneConfig = {
        url: 'fake',
        clickable: '.drop-clickable',
        parallelUploads: 1,
        uploadMultiple: false,
        maxFileSize: 30,
        paramName: "file", // The name that will be used to transfer the file
        maxFilesize: 5, // MB
        accept: function(file, done) {
            $scope.isUploading = true;
            $scope.$apply();
            done();
        },
        success: function(file, response) {
            if (response.success) {
                $scope.estimate.attachments.push(response.attachment);
                $scope.$apply();
            }
        },
        previewTemplate: angular.element('#dropzone-preview-template').html(), // Empty for now
        previewsContainer: '#attachment-previews', // Hidden for now
        complete: function(file) {
            $scope.isUploading = false;
            $scope.$apply();
        },
        uploadprogress: function(file, progress, bytesSent) {
            $scope.uploadProgress = progress.toFixed(0);
            $scope.$apply();
        },
        init: function() {
            this.on("processing", function(file) {
                this.options.url = ERPApp.baseAPIPath +
                    '&_do=uploadAttachment&data[id]=' +
                    $scope.estimate.id;
            });
        }
    };

    $scope.removeAttachment = function(attachment) {
        $ngBootbox.confirm('Are you sure want to remove the attachment?')
            .then(
                function() {
                    attachmentUploader.destroy(attachment.id)
                        .success(function(response) {
                            $scope.estimate.attachments.splice(
                                $scope.estimate.attachments.indexOf(attachment),
                                1);
                            toastr.success(response.message);
                        })
                        .error(function() {
                            toastr.error('An error has occurred while deleting attachment');
                        });
                }, function() {
                    // Do nothing
                }
            );
    };

    $scope.lineItemsDragListeners = {
        accept: function (sourceItemHandleScope, destSortableScope) {
            return true;
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

    $scope.sendMailEstimate = function() {
        $scope.showSendModal = false;
        estimateFactory.sendMail($scope.sendMailData)
            .success(function(response){
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            });
    };

    $scope.submitForm = function(sendMail) {
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
                        delete estimate.attachments;
                        if (estimate.txn_date) {
                            estimate.txn_date = $filter('date')(
                                estimate.txn_date,
                                "yyyy-MM-dd");
                        }
                        if (estimate.expiration_date) {
                            estimate.expiration_date = $filter('date')(
                                estimate.expiration_date, "yyyy-MM-dd");
                        }
                        if (estimate.date_of_signature) {
                            estimate.date_of_signature = $filter('date')(
                                estimate.date_of_signature, "yyyy-MM-dd");
                        }

                        if ($scope.isChangedSignature) {
                            estimate.customer_signature_encoded = $scope.signatureEncoded;
                        }
                        estimateFactory.update(estimate)
                            .success(function(response) {
                                if (response.success) {
                                    // Auto cange status to Accepted when the signature is added(once)
                                    if ($scope.isChangedSignature && !$scope.estimate.customer_signature) {
                                        $scope.estimate.status = 'Accepted';
                                    }
                                    $scope.isChangedSignature = false;
                                    toastr.success(response.message);
                                    if (sendMail) {
                                        $scope.sendMailData = {
                                            id: $scope.estimate.id,
                                            to: $scope.estimate.email,
                                            subject: 'Estimate from ' + $scope.companyInfo.name
                                        };
                                        $scope.sendMailForm.$setPristine();
                                        $scope.showSendModal = true;
                                    }
                                } else {
                                    var msg = response.message || 'An error occurred while saving estimate';
                                    toastr.error(msg);
                                }
                            })
                            .error(function() {
                                toastr.error('An error occurred while updating estimate');
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
        $scope.isChangedSignature = true;
    };

    var reloadAttachments = function() {
        estimateFactory.attachments($scope.estimate.id).
            success(function(responseData) {
                $scope.estimate.attachments = [];
                $scope.estimate.attachments = responseData;
            });
    };

    var getJobFullAddress = function() {
        return $scope.estimate.job_address + ' ' +
            $scope.estimate.job_city + ' ' +
            $scope.estimate.job_state + ' ' +
            $scope.estimate.job_zip_code;
    };
}
