/**
 * Directive for customer dropdown
 * A wrapper directive of selectize, for DRYing up the configs, and callbacks
 * Usage:
 *   <customers-select options="customers" ng-model="referral.customer_id">
 *   </customers-select>
 */
angular
    .module('Erp')
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
            controller: ['$scope', '$attrs', function($scope, $attrs) {
                // Default config
                var selectConfig = {
                    valueField: 'id',
                    labelField: 'display_name',
                    searchField: 'display_name',
                    sortField: 'order',
                    selectOnTab: true,
                    maxItems: 1,
                    maxOptions: 10000
                };

                // Create callback if specified
                if ('undefined' !== typeof($attrs.onAdd)) {
                    selectConfig.create = function(input, callback) {
                        var newCustomer = {
                            id: 0,
                            display_name: input,
                            order: $scope.selectOptions.length
                        };
                        // Remove the last new customer
                        angular.forEach($scope.selectOptions, function(cus, index) {
                            if (cus.id === 0) {
                                $scope.selectOptions.splice(index, 1);
                                return;
                            }
                        });
                        $scope.selectOptions.push(newCustomer);
                        $scope.onAdd({input: input});
                        callback(newCustomer);
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
            }]
        };
    });
