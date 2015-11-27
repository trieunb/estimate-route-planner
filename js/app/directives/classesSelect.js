angular
    .module('Erp')
    .directive('classesSelect', [classesSelect]);

function classesSelect() {
    return {
        require: 'ngModel',
        restrict: 'E',
        scope: {
            ngModel: '=',
            selectOptions: '=options',
            ngDisabled: '=',
            ngRequired: '&'
        },
        template: '<selectize ng-model="ngModel" config="selectConfig" options="selectOptions"></selectize>',
        controller: ['$scope', function($scope) {

            $scope.selectConfig = {
                valueField: 'id',
                labelField: 'name',
                searchField: 'name',
                sortField: 'name',
                selectOnTab: true,
                maxItems: 1,
                maxOptions: 300
            };
        }]
    };
}
