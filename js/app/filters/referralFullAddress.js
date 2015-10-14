angular.module('Erp')
    .filter('referralFullAddress', function() {
      return function(referral) {
        var fullAddress = '';
        var streetCity = [referral.address, referral.city].join(' ').trim();
        var stateZip = [referral.state, referral.zip_code].join(' ').trim();

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
