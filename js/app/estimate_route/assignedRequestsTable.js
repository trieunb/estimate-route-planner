/**
 * A custom component for listing Assigned Requests for estimate route
 * Just for DRYing up html code and easier to read.
 */
angular.module('Erp')
    .directive('assignedRequestsTable', function($rootScope) {
        return {
            restrict: 'E',
            templateUrl: $rootScope.templatesPath + 'estimate-route/assigned-requests-table.html'
        };
    });
