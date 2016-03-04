angular.module('Erp')
    .filter('datetimeToDate', function($filter) {
      return function(datetime, format) {
        if (datetime) {
            return $filter('date')(Date.parse(datetime), format);
        }
    };
});