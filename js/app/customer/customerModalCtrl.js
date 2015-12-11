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

            erpLocalStorage.getCustomers()
                .then(function(data) {
                    $scope.customers = [];
                    angular.copy(data, $scope.customers);
                });
            // Deteach to set first and last name by given entered full name
            var customerId = customerData.id;
            if ('undefined' !== typeof(customerId)) {
                customerFactory.show(customerId)
                    .success(function(customerInfo) {
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
                    // TODO: show validate messages
                }
            };

            $scope.close = function() {
                $uibModalInstance.dismiss();
            };
        }
);
