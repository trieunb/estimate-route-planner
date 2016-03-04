angular
  .module('Erp')
  .directive("datepickerAutoOpen", ["$parse", "$timeout", function($parse, $timeout) {
      return {
        link: function(scope, element, attrs) {
            var elmScope = element.isolateScope();
            element.on("click", function() {
              $timeout(function() {
                $parse("isOpen").assign(elmScope, "true");
                elmScope.$apply();
              });
            });
        }
      };
  }]);