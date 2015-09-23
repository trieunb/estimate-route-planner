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
        }
    })
    .directive('multipleEmails', function () {
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
    });
