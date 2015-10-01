 angular.module('Erp')
    .controller(
        'AddReferralRouteCtrl',
        [
            '$scope',
            '$rootScope',
            'jobRequestFactory',
            'referralRouteFactory',
            '$location',
            '$filter',
            'uiGmapGoogleMapApi',
            'uiGmapIsReady',
            AddReferralRouteCtrl
        ]
    );

function AddReferralRouteCtrl(
    $scope,
    $rootScope,
    jobRequestFactory,
    referralRouteFactory,
    $location,
    $filter,
    uiGmapGoogleMapApi,
    uiGmapIsReady) {

    var orderBy = $filter('orderBy');
    var directionsService = new google.maps.DirectionsService();
    var directionsDisplay = new google.maps.DirectionsRenderer({
        suppressMarkers: true // Hide direction marker
    });
    $scope.setPageTitle('Referral route planner');
    $scope.route = {}; // Form data
    $scope.pendingReferrals = [];
    $scope.assignedReferrals = [];
    $scope.recentRoutes = [];
    $scope.pendingMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/blue-marker.png' };
    $scope.map = {control: {}};  // Hold map instance
    $scope.map.options = {};
    $scope.assigned_queue_sort_by = '';
    $scope.pending_queue_sort_by = '';
    
    $scope.sortOptions = [
      {
        label: 'Custom',
        value: ''
      },
      {
        label: 'Status',
        value: 'status'
      },
      {
        label: 'Date Requested',
        value: 'date_requested'
      }
    ];
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

            // Setting gmap
            uiGmapGoogleMapApi.then(function(maps) {
                var bounds = new google.maps.LatLngBounds();
                angular.forEach($scope.pendingReferrals, function(referral) {
                    bounds.extend(new google.maps.LatLng(referral.coords.latitude, referral.coords.longitude));
                });
                $scope.map.options = {
                    center: {
                        latitude: bounds.getCenter().lat(),
                        longitude: bounds.getCenter().lng()
                    },
                    zoom: 14,
                    MapTypeId: maps.MapTypeId.HYBRID
                };

                return maps;
            });

           uiGmapIsReady.promise(1).then(function(instances) {
                directionsDisplay.setMap($scope.map.control.getGMap());
            });
            // Load recent saved routes
            referralRouteFactory.recent()
                .success(function(response) {
                    $scope.recentRoutes = response;
                });
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
        if ($scope.assignedReferrals.length < 2) {
            // Clear drawed direction
            directionsDisplay.set('directions', null);
            return;
        }
        var origin = {}; // start
        var destination = {}; // end
        var waypts = [];

        angular.forEach($scope.assignedReferrals, function(referral, index) {
            var point = {};
            var latLng = new google.maps.LatLng(referral.coords.latitude, referral.coords.longitude);
            point.location = latLng;

            if (index === 0) {
                origin = latLng;
            }

            if( (index + 1) === $scope.assignedReferrals.length ) {
                point.stopover = true;
                destination = latLng;
            }
            waypts.push(point);
        });

        var request = {
            origin : origin,
            waypoints: waypts,
            destination: destination,
            optimizeWaypoints: true,
            travelMode: google.maps.TravelMode.DRIVING
        };

        directionsService.route(request, function(response, status) {
            if (status == google.maps.DirectionsStatus.OK) {
                directionsDisplay.setDirections(response);
            }
        });
    };

    $scope.saveRoute = function() {
        if ($scope.assignedReferrals.length == 0) {
            toastr['error']("A route could not be saved without any assigned referrals!");
        } else {
            var data = {};
            data.title = $scope.route.title;
            data.assigned_referral_ids = [];
            angular.forEach($scope.assignedReferrals, function(referral) {
                data.assigned_referral_ids.push(referral.id);
            });
            referralRouteFactory.save(data)
                .success(function(response) {
                    if (response.success) {
                        toastr['success'](response.message);
                        $location.path('/edit-referral-route/' + response.data.id);
                    } else {
                        var msg = response.message || 'An error occurred while saving referral';
                        toastr['error'](msg);
                    }
                });
        }
    };

    $scope.printRoute = function() {
        if ($scope.assignedReferrals.length < 2) {
            toastr['error']("Print route require at least two assigned referrals!");
        } else {
            var url = 'https://www.google.com/maps/dir/am=t' + getGmapURL();
            window.open(url, '_blank');
        }
    };

    var getGmapURL = function() {
        var latlng = [] ;
        angular.forEach($scope.assignedReferrals, function(referral) {
            var tmp = [];
            referral.coords = {
                latitude: referral.lat,
                longitude: referral.lng
            };
            tmp.push(referral.coords.latitude);
            tmp.push(referral.coords.longitude);
            latlng.push(tmp);
        });

        var result = '';
        angular.forEach(latlng, function(value) {
            result += '/' + value[0] + ',' + value[1];
        });
        return result;
    };
}
