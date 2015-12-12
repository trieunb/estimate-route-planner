angular.module('Erp')
    .directive('customerTypeahead', [customerTypeahead]);

function customerTypeahead() {
    return {
        scope: {
            ngModel: '=',
            placeholder: '@',
            onSelect: '&'
        },
        controller: ['$scope', 'erpLocalStorage', function($scope, erpLocalStorage) {
                $scope.customers = [];

                erpLocalStorage.getCustomers()
                    .then(function(data) {
                        $scope.customers = data;
                    });

                $scope.typeaheadOnSelect = function($item, $model, $label) {
                    $scope.onSelect({item: $item});
                };
            }
        ],
        restrict: 'E',
        template: '<input class="form-control" typeahead-focus-first="false" typeahead-on-select="typeaheadOnSelect($item, $model, $label)" ng-model="ngModel" placeholder="{{placeholder}}" uib-typeahead="customer.display_name as customer.display_name for customer in ::customers | filter:$viewValue | limitTo:30" typeahead-wait-ms="200" placeholder="Search by customer name, number ... ">'
    };
}
