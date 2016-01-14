angular.module('Erp')
    .filter('multilineTextToHtml', function() {
        return function(text) {
            return text.replace(/\r\n|\r|\n/g,"<br />");
        };
    });
