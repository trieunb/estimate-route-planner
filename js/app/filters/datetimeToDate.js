angular.module('Erp')
    .filter('datetimeToDate', function($filter) {
      return function(datetime, format) {
        return $filter('date')(Date.parse(datetime), format);
    };
});