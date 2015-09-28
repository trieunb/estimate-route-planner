angular
    .module('Erp')
    .controller('EditEstimateCtrl', [
        '$scope',
        '$rootScope',
        '$http',
        '$routeParams',
        '$filter',
        '$location',
        'customerFactory',
        'estimateFactory',
        'employeeFactory',
        'attachmentUploader',
        '$ngBootbox',
        EditEstimateCtrl
    ]);

function EditEstimateCtrl($scope, $rootScope, $http, $routeParams, $filter, $location,
    customerFactory, estimateFactory, employeeFactory, attachmentUploader, $ngBootbox) {
    $rootScope.pageTitle = 'Estimate';
    $scope.customers = [];
    $scope.employees = [];
    $scope.estimate = {};
    $scope.uploadProgress = 0;
    $scope.isChangedSignature = false;
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
            if(response.success) {
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
               this.options.url = ERPApp.baseAPIPath + '&_do=uploadAttachment&data[id]=' + $scope.estimate.id;
            });
        }
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

    estimateFactory.show($routeParams.id)
        .success(function(response) {
            var estimate = response;
            $rootScope.pageTitle = 'Estimate #' + estimate.doc_number;
            // Assign line_num for the empty line as length of estimate lines
            angular.forEach(estimate.lines, function(line) {
                if (line.line_num == null) {
                    line.line_num = estimate.lines.length;
                } else {
                    line.line_num = parseInt(line.line_num);
                }

                if (line.rate != null) {
                    line.rate = parseFloat(line.rate);
                }

                if (line.qty != null) {
                    line.qty = parseInt(line.qty);
                }
            });
            // Order lines by line_num
            estimate.lines = $filter('orderBy')(estimate.lines, 'line_num', false);
            // Load signature to canvas if exists
            setTimeout(function() {
                var signaturePad = $scope.signature_pad;
                if (estimate.customer_signature) {
                    convertImgToBase64URL($rootScope.baseERPPluginUrl + estimate.customer_signature, function(encoded) {
                        signaturePad.fromDataURL(encoded);
                    });
                }
                signaturePad.onBegin = function() {
                    $scope.isChangedSignature = true;
                };
            }, 300);
            $scope.estimate = estimate;
            $scope.updateTotal();
        })
        .then(function() {
            $scope.isBusy = false;
        });

    $scope.removeAttachment = function(attachment) {
        $ngBootbox.confirm('Are you sure want to remove the attachment?')
            .then(
                function() {
                    console.log(attachment);
                    $rootScope.isBusy = true;
                    attachmentUploader.delete(attachment.id)
                        .success(function(response) {
                            $scope.estimate.attachments.splice($scope.estimate.attachments.indexOf(attachment), 1);
                            toastr['success'](response.message);
                        })
                        .error(function() {
                            toastr['error']('An error has occurred while deleting attachment');
                        })
                        .then(function() {
                            $rootScope.isBusy = false;
                        });
                }, function() {
                    // Do nothing
                }
            );
    };

    $scope.clearCustomerSignature = function() {
        var signaturePad = $scope.signature_pad;
        signaturePad.clear();
        $scope.isChangedSignature = true;
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
            if (line.product_service_id && !isNaN(line.rate) && !isNaN(line.qty)) {
                isEmpty = false;
            }
        });
        return isEmpty;
    };

    $scope.sendMailEstimate = function() {
        $scope.showSendModal = false;
        $rootScope.isBusy = true;
        estimateFactory.sendMail($scope.sendMailData)
            .success(function(response){
                if (response.success) {
                    toastr['success'](response.message);
                } else {
                    toastr['error'](response.message);
                }
            })
            .then(function() {
                $rootScope.isBusy = false;
            });
    };

    $scope.submitForm = function(sendMail) {
        if (isEmptyLines()) {
            toastr['error']('You must fill out at least one split line.');
        } else {
            $rootScope.isBusy = true;
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({ "address": getJobFullAddress() }, function(results, status) {
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

                    if ($scope.isChangedSignature) {
                        // Get base64 of customer signature
                        var signaturePad = $scope.signature_pad;
                        if(signaturePad.isEmpty()) {
                            estimate.customer_signature_encoded = '';
                        } else {
                            estimate.customer_signature_encoded = signaturePad.toDataURL();
                        }
                    }
                    estimateFactory.update(estimate)
                        .success(function(response) {
                            if (response.success) {
                                $scope.isChangedSignature = false;
                                toastr['success'](response.message);
                                if (sendMail) {
                                    $scope.sendMailData = {
                                        id: $scope.estimate.id,
                                        to: $scope.estimate.email,
                                        body: $scope.estimate.estimate_footer,
                                    };
                                    $scope.sendMailForm.$setPristine();
                                    $scope.showSendModal = true;
                                }
                            } else {
                                var msg = response.message || 'An error occurred while saving estimate';
                                toastr['error'](msg);
                            }
                        })
                        .error(function() {
                            toastr['error']('An error occurred while saving estimate');
                            $rootScope.isBusy = false;
                        })
                        .then(function() {
                            $rootScope.isBusy = false;
                        });
                } else {
                    $rootScope.isBusy = false;
                    $rootScope.$apply();
                    toastr['error']('Could not find geo location of job info. Please check the job address!');
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
