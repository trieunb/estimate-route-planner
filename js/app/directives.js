angular
    .module('Erp')
    .directive('requiredFile',function() {
        return {
            require: 'ngModel',
            link: function(scope, el, attrs, ngModel) {
                el.bind('change', function() {
                    scope.$apply(function() {
                        ngModel.$setViewValue(el.val());
                        ngModel.$render();
                    });
                });
            }
        };
    })
    .directive('multipleEmails', function() {
        return {
            require: 'ngModel',
            link: function(scope, element, attrs, ctrl) {
                ctrl.$parsers.unshift(function(viewValue) {
                    var emails = viewValue.split(',');
                    // define single email validator here
                    var re = /\S+@\S+\.\S+/;
                    var validityArr = emails.map(function(str) {
                        return re.test(str.trim());
                    }); // sample return is [true, true, true, false, false, false]
                    var atLeastOneInvalid = false;
                    angular.forEach(validityArr, function(value) {
                        if(value === false)
                        atLeastOneInvalid = true;
                    });
                    if (!atLeastOneInvalid) {
                        ctrl.$setValidity('multipleEmails', true);
                        return viewValue;
                    } else {
                        ctrl.$setValidity('multipleEmails', false);
                        return undefined;
                    }
                });
            }
        };
    })
    /**
     * Directive for customer dropdown
     * A wrapper directive of selectize, for DRYing up the configs, and callbacks
     * Usage:
     *   <customers-select options="customers" ng-model="referral.customer_id">
     *   </customers-select>
     */
    .directive('customersSelect', function() {
        return {
            restrict: 'E',
            scope: {
                ngModel: '=',
                selectOptions: '=options',
                ngDisabled: '=',
                ngRequired: '&',
                onAdd: '&'
            },
            template: '<selectize ng-model="ngModel" config="selectConfig" options="selectOptions"></selectize>',
            controller: ['$scope', function($scope) {
                // Default config
                var selectConfig = {
                    valueField: 'id',
                    labelField: 'display_name',
                    sortField: 'display_name',
                    searchField: 'display_name',
                    selectOnTab: true,
                    maxItems: 1,
                    maxOptions: 10000,
                };

                // Create callback
                selectConfig.create = function(input, callback) {
                    var newCustomer = {
                        id: 0,
                        display_name: input
                    };
                    // Remove the last new customer
                    angular.forEach($scope.selectOptions, function(cus, index) {
                        if (cus.id === 0) {
                            $scope.selectOptions.splice(index, 1);
                            return;
                        }
                    });
                    $scope.selectOptions.push(newCustomer);
                    // scope.referral.customer_display_name = input;
                    $scope.onAdd(input);
                    callback(newCustomer);
                };

                // Custom rendering for displaying sub-customers
                selectConfig.render = {
                        option: function(item, escape) {
                            var itemClass = 'option ';
                            var itemText = item.display_name;
                            if (null !== item.parent_id && item.parent_id !== '0') {
                                itemClass += 'sub ';
                                itemClass += 'sub-level-' + item.sub_level;
                                itemText += '<small> Sub-customer of <b>' + item.parent_display_name + '</b></small>';
                            }
                            return '<div class="' + itemClass + '">' + itemText + '</div>';
                        }
                    };
                $scope.selectConfig = selectConfig;
            }]
        };
    });
