angular
    .module('Erp')
    .controller(
        'customerModalCtrl',
        function(
            $scope,
            $rootScope,
            $uibModalInstance,
            customerFactory,
            erpLocalStorage,
            customerData) {

            $scope.updateFormFlag = true;
            $scope.customer = {};
            $scope.customers = [];

            if (!$scope.hasCap('erpp_restrict_client_dropdown')) {
                erpLocalStorage.getCustomers()
                    .then(function(data) {
                        $scope.customers = [];
                        angular.copy(data, $scope.customers);
                    });
            }

            // Deteach to set first and last name by given entered full name
            var customerId = customerData.id;
            if ('undefined' !== typeof(customerId)) { // Edit mode
                customerFactory.show(customerId)
                    .success(function(customerInfo) {
                        if ($scope.hasCap('erpp_restrict_client_dropdown') && customerInfo.parent_id) {
                            // Only parent customer available
                            $scope.customers.push(customerInfo.parent);
                        }
                        $scope.customer = customerInfo;
                    });
            } else {
                var customerName = customerData.name.trim();
                if (customerName.length > 0) {
                    var spacePos = customerName.indexOf(" ");
                    if (spacePos !== -1) {
                        $scope.customer.given_name =
                            customerName.substr(0, spacePos).trim();
                        $scope.customer.family_name =
                            customerName.substr(spacePos + 1).trim();
                    } else {
                        $scope.customer.given_name = customerName;
                    }
                }
            }

            $scope.validateForm = function() {
                return $scope.customerForm.$valid;
            };

            $scope.fillDisplayName = function() {
                // Only auto fill when add new customer and
                // the Display Name input is not dirty
                if (!$scope.customerForm.display_name.$dirty && !$scope.customer.id) {
                    var displayName = '';

                    if ($scope.customer.family_name) {
                        displayName = $scope.customer.family_name;
                    }

                    if ($scope.customer.given_name && displayName) {
                        displayName += ', ' + $scope.customer.given_name;
                    } else if($scope.customer.given_name) {
                        displayName = $scope.customer.given_name;
                    }
                    $scope.customer.display_name = displayName.trim();
                }
            };

            $scope.onParentChange = function() {
                if ($scope.customer.parent_id !== undefined) {
                    for (var i = 0; i < $scope.customers.length; i++) {
                        if ($scope.customers[i].id == $scope.customer.parent_id) {
                            var parentCustomer = $scope.customers[i];
                            var autoFillFields = [
                                'email',
                                'primary_phone_number',
                                'mobile_phone_number',
                                'bill_address',
                                'bill_city',
                                'bill_state',
                                'bill_zip_code',
                                'bill_country',
                            ];
                            for (var j = 0; j < autoFillFields.length; j++) {
                                var field = autoFillFields[j];
                                if (parentCustomer[field]) {
                                    $scope.customer[field] = parentCustomer[field];
                                }
                            }
                            if (parentCustomer.company_name) {
                                $scope.customer.company_name = parentCustomer.company_name;
                            } else {
                                $scope.customer.company_name = parentCustomer.display_name;
                            }
                            break;
                        }
                    }
                }

            };

            $scope.fillShippingWithBilling = function() {
                var addressFields = [
                    'address',
                    'city',
                    'state',
                    'zip_code',
                    'country'
                ];
                for (var i = 0; i < addressFields.length; i++) {
                    var field = addressFields[i];
                    $scope.customer['ship_' + field] = $scope.customer['bill_' + field];
                }
            };

            $scope.save = function() {
                if ($scope.validateForm()) {
                    customerFactory.create($scope.customer)
                        .success(function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                $uibModalInstance.close({
                                    customer: response.customer,
                                    updateFormFlag: $scope.updateFormFlag}
                                );
                            }
                        })
                        .error(function() {
                            toastr.error('An error occurred while saving customer');
                        });
                } else {
                    toastr.warning("Please fill out required fields");
                }
            };

            $scope.close = function() {
                $uibModalInstance.dismiss();
            };
        }
);
