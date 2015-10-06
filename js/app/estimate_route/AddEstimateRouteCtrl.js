 angular.module('Erp')
    .controller(
        'AddEstimateRouteCtrl',
        [
            '$scope',
            '$rootScope',
            'jobRequestFactory',
            'estimateRouteFactory',
            'sharedData',
            '$location',
            '$filter',
            'erpOptions',
            'uiGmapGoogleMapApi',
            'uiGmapIsReady',
            'erpGeoLocation',
            AddEstimateRouteCtrl
        ]
    );

function AddEstimateRouteCtrl(
    $scope,
    $rootScope,
    jobRequestFactory,
    estimateRouteFactory,
    sharedData,
    $location,
    $filter,
    erpOptions,
    uiGmapGoogleMapApi,
    uiGmapIsReady,
    erpGeoLocation) {

    var orderBy = $filter('orderBy');
    var directionsService = new google.maps.DirectionsService();
    var directionsDisplay = new google.maps.DirectionsRenderer({
        suppressMarkers: true // Hide direction marker
    });
    $scope.setPageTitle('New Estimate Route');
    $scope.route = {}; // Form data
    $scope.pendingReferrals = [];
    $scope.assignedReferrals = [];
    $scope.recentRoutes = [];
    $scope.pendingMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/blue-marker.png' };
    $scope.startMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/start-marker.png' };
    $scope.map = {control: {}};  // Hold map instance
    $scope.map.options = {};
    $scope.assigned_queue_sort_by = '';
    $scope.pending_queue_sort_by = '';
    $scope.routeOrigin = null;
    $scope.routeOriginAddress = sharedData.companyInfo.full_address;
    $scope.sortOptions = erpOptions.sortEstimateRoute;

    // Find start location for route(use company address)
    erpGeoLocation.resolve($scope.routeOriginAddress)
        .then(
            function(result) {
                $scope.routeOrigin = {
                    latitude: result.lat(),
                    longitude: result.lng()
                };

                // Setting gmap
                uiGmapGoogleMapApi.then(function(maps) {
                    $scope.map.options = {
                        center: {
                            latitude: $scope.routeOrigin.latitude,
                            longitude: $scope.routeOrigin.longitude
                        },
                        zoom: 14
                    };
                    return maps;
                });

               uiGmapIsReady.promise(1).then(function(instances) {
                    directionsDisplay.setMap($scope.map.control.getGMap());
                });
            },
            function() {
                toastr.error('Could not find geo location of company address! The route could not draw!');
            }
        );

    // Loading referrals
    jobRequestFactory.listPending()
        .success(function(response) {
            // Collect pending referrals for dragging
            angular.forEach(response, function(referral) {
                referral.coords = {
                    latitude: referral.lat,
                    longitude: referral.lng
                };
                $scope.pendingReferrals.push(referral);
            });
        });
    // Load recent saved routes
    estimateRouteFactory.recent()
        .success(function(response) {
            $scope.recentRoutes = response;
        });

    $scope.onDropToAssignedQueue = function(event, index, item, external, type) {
        $scope.assigned_queue_sort_by = '';
        return item;
    };

    $scope.onDropToPendingQueue = function(event, index, item, external, type) {
        $scope.pending_queue_sort_by = '';
        return item;
    };

    $scope.onPendingMoved = function(referral, referralIndex) {
        $scope.pendingReferrals.splice(referralIndex, 1);
        $scope.drawAssignedReferralsDirection();
    };

    $scope.onAssignedMoved = function(referral, referralIndex) {
        $scope.assignedReferrals.splice(referralIndex, 1);
        $scope.drawAssignedReferralsDirection();
    };

    $scope.sortAssignedQueue = function() {
        $scope.assignedReferrals = orderBy(
            $scope.assignedReferrals,
            $scope.assigned_queue_sort_by,
            false
        );
        $scope.drawAssignedReferralsDirection();
    };

    $scope.sortPendingQueue = function() {
        $scope.pendingReferrals = orderBy(
            $scope.pendingReferrals,
            $scope.pending_queue_sort_by,
            false
        );
    };

    /**
     * Repaint direction
     */
    $scope.drawAssignedReferralsDirection = function() {
        if ($scope.routeOrigin === null) {
            toastr.error('Could not find geo location of company address! The route could not draw!');
            return;
        }
        if ($scope.assignedReferrals.length < 1) {
            // Clear drawed direction
            directionsDisplay.set('directions', null);
            return;
        }
        var originLatLng = new google.maps.LatLng(
            $scope.routeOrigin.latitude, $scope.routeOrigin.longitude);
        var destination = {}; // end
        var waypts = [
            {
                location: originLatLng
            }
        ];

        angular.forEach($scope.assignedReferrals, function(referral, index) {
            var point = {};
            var latLng = new google.maps.LatLng(referral.coords.latitude, referral.coords.longitude);
            point.location = latLng;

            if( (index + 1) === $scope.assignedReferrals.length ) {
                point.stopover = true;
                destination = latLng;
            }
            waypts.push(point);
        });

        var request = {
            origin: originLatLng,
            waypoints: waypts,
            destination: destination,
            optimizeWaypoints: true,
            travelMode: google.maps.TravelMode.DRIVING
        };

        directionsService.route(request, function(response, status) {
            if (status == google.maps.DirectionsStatus.OK) {
                directionsDisplay.setDirections(response);
            } else {
                toastr.error("Could not find route on the map!");
            }
        });
    };

    $scope.saveRoute = function() {
        if ($scope.assignedReferrals.length === 0) {
            toastr.error("A route could not be saved without any assigned referrals!");
        } else {
            var data = {};
            data.title = $scope.route.title;
            data.assigned_referral_ids = [];
            angular.forEach($scope.assignedReferrals, function(referral) {
                data.assigned_referral_ids.push(referral.id);
            });
            estimateRouteFactory.save(data)
                .success(function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $location.path('/edit-estimate-route/' + response.data.id);
                    } else {
                        var msg = response.message || 'An error occurred while saving route';
                        toastr.error(msg);
                    }
                });
        }
    };

    $scope.printRoute = function() {
        if ($scope.routeOrigin === null) {
            toastr.error('Could not find geo location of company address! The route could not print!');
            return;
        }
        if ($scope.assignedReferrals.length < 1) {
            toastr.error("Print route require at least 1 assigned referral!");
        } else {
            var url = 'https://www.google.com/maps/dir/am=t' + getGmapURL();
            window.open(url, '_blank');
        }
    };

    var getGmapURL = function() {
        var latlngs = [] ;
        latlngs.push({
            latitude: $scope.routeOrigin.latitude,
            longitude: $scope.routeOrigin.longitude
        });
        angular.forEach($scope.assignedReferrals, function(referral) {
            latlngs.push(referral.coords);
        });

        var result = '';
        angular.forEach(latlngs, function(point) {
            result += '/' + point.latitude + ',' + point.longitude;
        });
        return result;
    };
}
