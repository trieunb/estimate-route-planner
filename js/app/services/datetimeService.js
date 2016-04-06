angular.module('Erp')
    .factory('datetimeService', [datetimeService]);

function datetimeService() {
    return {
        /**
         * Convert a date in string(yyyy-MM-dd) to object in local timezone
         */
        stringToDate: function(dateStr) {
            var returnDate = new Date();
            var dateParts = dateStr.split('-');
            returnDate.setYear(dateParts[0]);
            returnDate.setMonth(parseInt(dateParts[1]) - 1);
            returnDate.setDate(dateParts[2]);
            return returnDate;
        }
    };
}
