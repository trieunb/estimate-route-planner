angular
    .module('Erp')
    .directive('requiredFile',function() {
        return {
            require:'ngModel',
            link: function(scope,el,attrs,ngModel) {
                el.bind('change',function() {
                    scope.$apply(function() {
                        ngModel.$setViewValue(el.val());
                        ngModel.$render();
                    });
                });
            }
      }
    });