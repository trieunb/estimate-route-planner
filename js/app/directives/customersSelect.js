/**
 * Directive for customer dropdown
 * A wrapper directive of selectize, for DRYing up the configs, and callbacks
 * Usage:
 *   <customers-select options="customers" ng-model="referral.customer_id">
 *   </customers-select>
 */
angular
    .module('Erp')
    .directive('customersSelect', ['APP_CONFIG', function(APP_CONFIG) {
        return {
            restrict: 'E',
            scope: {
                ngModel: '=',
                selectOptions: '=options',
                ngDisabled: '=',
                ngRequired: '&',
                ngChange: '=',
                onCustomerUpdate: '&', // Current customer's profile has been updated
                onCustomerChange: '&', // User select another customer
                onCustomerCreated: '&' // User created new customer
            },
            templateUrl: APP_CONFIG.templatesPath + 'customer/select-box.html',
            controller: [
                '$scope', '$attrs', '$uibModal', 'APP_CONFIG', 'erpLocalStorage', '$timeout', 'customerFactory',
                function($scope, $attrs, $uibModal, APP_CONFIG, erpLocalStorage, $timeout, customerFactory) {
                $scope.addingEnabled = $scope.editingEnabled = false;

                $scope.onCustomerChangeEvent = function() {
                    // Notify parent scope
                    // Use timeout to workaround issue: the model value still not really update here
                    $timeout(function() {
                        $scope.onCustomerChange();
                    });
                };

                if ('undefined' === typeof($attrs.addEnabled)) {
                    $scope.addingEnabled = true;
                } else {
                    $scope.addingEnabled = ($attrs.addEnabled == 'true');
                }

                if ('undefined' === typeof($attrs.editEnabled)) {
                    $scope.editingEnabled = true;
                } else {
                    $scope.editingEnabled = ($attrs.editEnabled == 'true');
                }

                // Default selectize config
                var selectConfig = {
                    valueField: 'id',
                    labelField: 'display_name',
                    searchField: 'display_name',
                    sortField: 'order',
                    selectOnTab: true,
                    maxItems: 1,
                    maxOptions: 500
                };

                // Create callback
                if ($scope.addingEnabled) {
                    selectConfig.create = function(input, callback) {
                        $scope.onAddCustomer(input);
                        callback();
                    };
                } else {
                    selectConfig.create = false;
                }

                // Custom rendering for displaying sub-customers
                selectConfig.render = {
                    option: function(item, escape) {
                        var itemClass = 'option ';
                        var itemText = item.display_name;
                        if (null !== item.parent_id &&
                                ('undefined' !== typeof(item.parent_display_name)) &&
                                item.parent_id !== '0') {
                            itemClass += 'sub ';
                            itemClass += 'sub-level-' + item.sub_level;
                            itemText += '<small> Sub-customer of <b>' + item.parent_display_name + '</b></small>';
                        }
                        return '<div class="' + itemClass + '">' + itemText + '</div>';
                    }
                };
                $scope.selectConfig = selectConfig;

                // Customer modal
                var getDefaultModalOpts = function() {
                    return {
                        animation: true,
                        backdrop: true,
                        keyboard: false,
                        templateUrl: APP_CONFIG.templatesPath + 'customer/modal.html',
                        controller: 'customerModalCtrl',
                        size: 'lg'
                    };
                };

                $scope.onEditCustomer = function() {
                    var modalOpts = getDefaultModalOpts();
                    modalOpts.resolve = {
                        customerData: function() {
                            return {
                                id: $scope.ngModel
                            };
                        }
                    };
                    var modalInstance = $uibModal.open(modalOpts);
                    modalInstance.result.then(function(returnData) {
                        // Update customers dropdown
                        erpLocalStorage.updateCustomer(returnData.customer);
                        for (var i = 0; i < $scope.selectOptions.length; i++) {
                            if ($scope.selectOptions[i].id == returnData.customer.id) {
                                $scope.selectOptions[i] = returnData.customer;
                                break;
                            }
                        }
                        // Update parent scope model
                        if (returnData.updateFormFlag) {
                            $scope.onCustomerUpdate();
                        }
                    }, function() {
                    });
                };

                $scope.onAddCustomer = function(input) {
                    if ('undefined' === typeof(input)) {
                        input = '';
                    }
                    var modalOpts = getDefaultModalOpts();
                    modalOpts.resolve = {
                        customerData: function() {
                            return {
                                name: input
                            };
                        }
                    };

                    var modalInstance = $uibModal.open(modalOpts);
                    modalInstance.result.then(function(returnData) {
                        // Update customers dropdown
                        erpLocalStorage.addCustomer(returnData.customer);
                        $scope.selectOptions.push(returnData.customer);
                        $scope.onCustomerCreated();

                        // Update parent scope model to the new customer
                        $scope.ngModel = returnData.customer.id;
                        $timeout(function() {
                            if (returnData.updateFormFlag) {
                                $scope.onCustomerChange();
                            }
                        }, 100);
                    }, function() {
                    });
                };
            }]
        };
    }]);
