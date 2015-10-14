angular.module('Erp')
    .filter('estimateJobFullAddress', function() {
      return function(estimate) {
        var fullAddress = '';
        var streetCity = [estimate.job_address, estimate.job_city].join(' ').trim();
        var stateZip = [estimate.job_state, estimate.job_zip_code].join(' ').trim();

        if (streetCity.length > 0) {
            fullAddress = streetCity;
        }
        if (fullAddress.length > 0 && stateZip.length > 0) {
            fullAddress += ', ' + stateZip;
        } else {
            fullAddress = streetCity;
        }
        return fullAddress;
    };
});
