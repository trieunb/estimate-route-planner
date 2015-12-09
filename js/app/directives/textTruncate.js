angular
    .module('Erp')
    .directive('textTruncate', [textTruncate]);

function textTruncate() {
    return {
        restrict: 'E',
        scope: {
            text: '=text',
            limit: '=limit'
        },
        template: '<p>{{::displayText}} <span title="More" ng-if="text.length > 0" class="btn-show-truncated-details" ng-click="showDetail()">{{endBy}}</span> <p>',
        link: function(scope, el, attrs, ngModel) {
            var DEFAULT_END_BY = ' ...?';
            var DEFAULT_LIMIT = 20;

            var truncate = function(text, limit, endBy) {
                if (text.length <= limit) {
                    return text;
                } else {
                    return String(text).substring(0, limit - endBy.length);
                }
            };

            if (null === scope.text) {
                scope.text = '';
            }
            if (scope.limit <= 0) {
                scope.limit = DEFAULT_LIMIT;
            }
            scope.displayText = '';
            scope.displayText = truncate(scope.text, scope.limit, DEFAULT_END_BY);
            scope.endBy = DEFAULT_END_BY;
        },
        controller: ['$scope', '$ngBootbox', function($scope, $ngBootbox) {
            function htmlEntities(str) {
                return String(str).replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }
            $scope.showDetail = function() {
                $ngBootbox.alert(htmlEntities($scope.text));
            };
        }]
    };
}
