angular.module('Erp')
    .filter('datetimeToDate', function($filter) {
      return function(datetime, format) {
        var date = Date.parse(datetime)
        return $filter('date')(date, format);
    };
});