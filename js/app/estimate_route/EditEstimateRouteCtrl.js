angular.module('Erp')
    .controller(
        'EditEstimateRouteCtrl',
        [
            '$scope',
            '$rootScope',
            'jobRequestFactory',
            'estimateRouteFactory',
            'sharedData',
            '$location',
            '$routeParams',
            'uiGmapGoogleMapApi',
            'uiGmapIsReady',
            '$filter',
            'erpOptions',
            'erpGeoLocation',
            EditEstimateRouteCtrl
        ]
    );

function EditEstimateRouteCtrl(
    $scope,
    $rootScope,
    jobRequestFactory,
    estimateRouteFactory,
    sharedData,
    $location,
    $routeParams,
    uiGmapGoogleMapApi,
    uiGmapIsReady,
    $filter,
    erpOptions,
    erpGeoLocation) {

    var orderBy = $filter('orderBy');
    var directionsService = new google.maps.DirectionsService();
    var directionsDisplay = new google.maps.DirectionsRenderer({
        suppressMarkers: true // Hide direction marker
    });

    $scope.setPageTitle('Estimate Route Planner');
    $scope.route = {}; // Form data
    $scope.pendingReferrals = [];
    $scope.assignedReferrals = [];
    $scope.pendingMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/blue-marker.png' };
    $scope.startMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/start-marker.png' };
    $scope.map = {control: {}}; // Hold map instance
    $scope.map.options = {};
    $scope.currentAssignedReferrals = [];
    $scope.assigned_queue_sort_by = '';
    $scope.pending_queue_sort_by = '';
    $scope.routeOrigin = null;
    $scope.routeOriginAddress = sharedData.companyInfo.full_address;
    $scope.sortOptions = erpOptions.sortEstimateRoute;

    // Get route data
    estimateRouteFactory.get($routeParams.id)
        .success(function(response) {
            $scope.route.id = response.id;
            $scope.route.title = response.title;
            $scope.route.status = response.status;

            // Collect assigned referrals for dragging
            angular.forEach(response.assigned_referrals, function(referral) {
                referral.coords = {
                    latitude: referral.lat,
                    longitude: referral.lng
                };
                $scope.assignedReferrals.push(referral);
                $scope.currentAssignedReferrals.push(referral);
            });

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
                            $scope.drawRouteDirection(); // Render direction after loaded
                        });
                    },
                    function() {
                        toastr.error('Could not find geo location of company address! The route could not draw!');
                    }
                );
        });

    // Loading referrals
    jobRequestFactory.listPending()
        .success(function(response) {
            // Collect pending referral for dragging
            angular.forEach(response, function(referral) {
                if(referral.lat && referral.lng) {
                    referral.coords = {
                        latitude: referral.lat,
                        longitude: referral.lng
                    };
                    $scope.pendingReferrals.push(referral);
                }
            });
        });

    $scope.assignedListDndOptions = {
        dragStart: function() {
            $scope.assigned_queue_sort_by = '';
            return true;
        },
        dropped: function(evt) {
            // Check for a real moving
            if ( (evt.source.nodesScope.$id != evt.dest.nodesScope.$id) ||
                (evt.dest.index != evt.source.index) ) {
                $scope.drawRouteDirection();
            }
            return true;
        }
    };

    $scope.pendingListDndOptions = {
        dragStart: function(evt) {
            $scope.pending_queue_sort_by = '';
            return true;
        },
        dropped: function(evt) {
            if (evt.source.nodesScope.$id != evt.dest.nodesScope.$id) {
                $scope.drawRouteDirection();
            }
            return true;
        }
    };

    $scope.sortAssignedQueue = function() {
        if ($scope.assignedReferrals.length > 1) {
            $scope.assignedReferrals = orderBy(
                $scope.assignedReferrals,
                $scope.assigned_queue_sort_by,
                false
            );
            $scope.drawRouteDirection();
        }
    };

    $scope.sortPendingQueue = function() {
        if ($scope.pendingReferrals.length > 1) {
            $scope.pendingReferrals = orderBy(
                $scope.pendingReferrals,
                $scope.pending_queue_sort_by,
                false
            );
        }
    };

    /**
     * Repaint direction
     */
    $scope.drawRouteDirection = function() {
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
        var data = {};
        data.id = $scope.route.id;
        data.title = $scope.route.title;
        data.status = $scope.route.status;
        data.assigned_referral_ids = [];
        angular.forEach($scope.assignedReferrals, function(referral) {
            data.assigned_referral_ids.push(referral.id);
        });
        estimateRouteFactory.update(data)
            .success(function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    // Change status of list referrals after save
                    angular.forEach($scope.assignedReferrals, function(referral) {
                        if (referral.status == 'Pending') {
                            referral.status = 'Assigned';
                        }
                    });
                    angular.forEach($scope.pendingReferrals, function(referral) {
                        if (referral.status == 'Assigned') {
                            referral.status = 'Pending';
                        }
                    });
                    $scope.currentAssignedReferrals = [];
                    angular.copy($scope.assignedReferrals, $scope.currentAssignedReferrals);
                } else {
                    var msg = response.message || 'An error occurred while saving referral';
                    toastr.error(msg);
                }
            });
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
