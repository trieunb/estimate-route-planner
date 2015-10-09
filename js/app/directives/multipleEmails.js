/**
 * Multiple emails input validation
 */
angular
    .module('Erp')
    .directive('multipleEmails', function() {
        return {
            require: 'ngModel',
            link: function(scope, element, attrs, ctrl) {
                ctrl.$parsers.unshift(function(viewValue) {
                    var emails = viewValue.split(',');
                    var re = /\S+@\S+\.\S+/;
                    var validityArr = emails.map(function(str) {
                        return re.test(str.trim());
                    });
                    var atLeastOneInvalid = false;
                    angular.forEach(validityArr, function(value) {
                        if(value === false) {
                            atLeastOneInvalid = true;
                        }
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
