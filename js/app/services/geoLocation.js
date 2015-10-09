/**
 * For geo location
 */
angular
    .module('Erp')
    .service('erpGeoLocation', ['$q', erpGeoLocation]);

function erpGeoLocation($q) {
    var geocoder = new google.maps.Geocoder();

    /**
     * Resolve the given address to Location object
     */
    this.resolve = function(address) {
        return $q(function(success, fails) {
            geocoder.geocode( { address: address }, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK && results.length > 0) {
                    success(results[0].geometry.location);
                } else {
                    fails();
                }
            });
        });
    };
}
