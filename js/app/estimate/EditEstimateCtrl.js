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
            'emailComposer',
            'erpOptions',
            'datetimeService',
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
        emailComposer,
        erpOptions,
        datetimeService,
        $ngBootbox,
        $window) {

    $scope.setPageTitle('Estimate');
    $scope.customers = [];
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
    $scope.jobPriorities = erpOptions.jobPriorities;

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

    // Product and services list
    erpLocalStorage.getProductServices()
        .then(function(services) {
            var activeServices = [];
            for (var i = 0; i < services.length; i++) {
                if (services[i].active == 1) {
                    activeServices.push(services[i]);
                }
            }
            angular.copy(activeServices, $scope.productServices);
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
            for (var i = 0; i < estimate.lines.length; i++) {
                var line = estimate.lines[i];
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
            };

            // Check if the line is an inactive service
            erpLocalStorage.getProductServices()
                .then(function(services) {
                    for (var i = 0; i < services.length; i++) {
                        if ((services[i].active == 0) && (lineProductServiceIds.indexOf(services[i].id) !== -1)) {
                            $scope.productServices.push(services[i]);
                        }
                    };
                });

            // Order lines by line_num
            estimate.lines = $filter('orderBy')(estimate.lines, 'line_num', false);

            // Load signature canvas
            if (estimate.customer_signature) {
                $scope.signatureEncoded = $rootScope.baseERPPluginUrl + estimate.customer_signature;
            }

            $scope.estimate = estimate;
            if ($scope.estimate.txn_date) {
                $scope.estimate.txn_date = datetimeService.stringToDate(estimate.txn_date);
            }
            if ($scope.estimate.expiration_date) {
                $scope.estimate.expiration_date = datetimeService.stringToDate(estimate.expiration_date);
            }
            if ($scope.estimate.date_of_signature) {
                $scope.estimate.date_of_signature = datetimeService.stringToDate(estimate.date_of_signature);
            }
            $scope.updateTotal();

            // Load customers
            if ($scope.hasCap('erpp_restrict_client_dropdown')) {
                $scope.customers = [];
                var visibleCustomers = [estimate.customer];
                if (estimate.customer.id != estimate.job_customer.id) {
                    visibleCustomers.push(estimate.job_customer);
                }
                angular.copy(visibleCustomers, $scope.customers);
            } else {
                erpLocalStorage.getCustomers()
                    .then(function(data) {
                        $scope.customers = [];
                        angular.copy(data, $scope.customers);
                        if ($scope.estimate.customer.active === '0') {
                            $scope.customers.push($scope.estimate.customer);
                        }
                        if ($scope.estimate.job_customer.active === '0') {
                            $scope.customers.push($scope.estimate.job_customer);
                        }
                    });
            }

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

    // When the current customer's profile has been updated in the modal
    // And 'Update Form' is checked
    $scope.onBillCustomerUpdate = function() {
        resetBillCustomer();
        if (isTheSameCustomer()) {
            resetJobCustomer();
        }
    };

    // Handler customer change to populate fields
    $scope.onBillCustomerChange = function() {
        resetBillCustomer();
    };

    $scope.onJobCustomerUpdate = function() {
        resetJobCustomer();
        if (isTheSameCustomer()) {
            resetBillCustomer();
        }
    };

    $scope.onJobCustomerChange = function() {
        resetJobCustomer();
    };

    var getBillCustomer = function() {
        for (var i = 0; i < $scope.customers.length; i++) {
            var cus = $scope.customers[i];
            if (cus.id == $scope.estimate.customer_id) {
                return cus;
            }
        }
    };

    var resetBillCustomer = function() {
        if ('undefined' !== typeof($scope.estimate.customer_id)) {
            for (var i = 0; i < $scope.customers.length; i++) {
                var cus = $scope.customers[i];
                if (cus.id == $scope.estimate.customer_id) {
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
        }
    };

    var resetJobCustomer = function() {
        if ('undefined' !== typeof($scope.estimate.job_customer_id)) {
            for (var i = 0; i < $scope.customers.length; i++) {
                var cus = $scope.customers[i];
                if (cus.id == $scope.estimate.job_customer_id) {
                    $scope.estimate.job_address = cus.ship_address;
                    $scope.estimate.job_city = cus.ship_city;
                    $scope.estimate.job_state = cus.ship_state;
                    $scope.estimate.job_zip_code = cus.ship_zip_code;
                    $scope.estimate.job_country = cus.ship_country;
                    $scope.estimate.job_company_name = cus.company_name;
                    break;
                }
            }
        }
    };

    $scope.updateTotal = function() {
        var total = 0.0;
        if ($scope.estimate.lines.length > 0) {
            for (var i = $scope.estimate.lines.length - 1; i >= 0; i--) {
                var line = $scope.estimate.lines[i];
                var rate = 0;
                var qty = 0;
                if (line.qty) {
                    qty = parseInt(line.qty);
                }
                if (line.rate) {
                    rate = parseFloat(line.rate);
                }

                if (line.product_service_id) {
                    if (line.qty == null || line.rate == null) {
                        line.total = null;
                    } else {
                        var lineTotal = rate * qty;
                        line.total = lineTotal;
                        total += lineTotal;
                    }
                }
            }
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

    $scope.submitForm = function(sendMail, print) {
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
                                            doc_number: $scope.estimate.doc_number,
                                            subject: 'Estimate from ' + $scope.companyInfo.name,
                                            body: emailComposer.getEstimateEmailContent(estimate, getBillCustomer())
                                        };
                                        $scope.sendMailForm.$setPristine();
                                        $scope.showSendModal = true;
                                        setTimeout(function() {
                                            angular.element('.estimate-mail-content')[0].focus();
                                        });
                                    }
                                    if (print) {
                                        window.open(ERPApp.baseAPIPath + '&_do=printEstimate&id=' + $scope.estimate.id, '_blank');
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

    $scope.previewPdfEstimate = function(estimate) {
        $scope.showModalPdf = true;
        $scope.sendMailData.id = estimate.id;
    }

    $scope.showSignatureBox = function() {
        $scope.isShowModalSignature = true;
    };

    $scope.onSaveSignature = function(signature) {
        $scope.signatureEncoded = signature;
        $scope.isChangedSignature = true;
        if (!$scope.estimate.date_of_signature) {
            $scope.estimate.date_of_signature = new Date();
        }
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
