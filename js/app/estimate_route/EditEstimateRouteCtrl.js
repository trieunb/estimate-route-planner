angular.module('Erp')
    .controller(
        'EditEstimateRouteCtrl',
        [
            '$scope',
            '$rootScope',
            'jobRequestFactory',
            'estimateRouteFactory',
            'sharedData',
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
    $routeParams,
    uiGmapGoogleMapApi,
    uiGmapIsReady,
    $filter,
    erpOptions,
    erpGeoLocation) {

    var orderBy = $filter('orderBy');
    var directionsService = new google.maps.DirectionsService();

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
    $scope.directionRenderers = [];

    // Get route data
    estimateRouteFactory.get($routeParams.id)
        .success(function(response) {
            $scope.route.id = response.id;
            $scope.route.title = response.title;
            $scope.route.status = response.status;
            $scope.route.estimator_id = response.estimator_id;

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

    var clearDirections = function() {
        for(var i = 0; i < $scope.directionRenderers.length; i++) {
            $scope.directionRenderers[i].set('directions', null);
            delete $scope.directionRenderers[i];
        }
        $scope.directionRenderers = [];
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
            clearDirections();
            return;
        }
        clearDirections();
        $scope.loadingOn();

        var originLatLng = new google.maps.LatLng(
            $scope.routeOrigin.latitude, $scope.routeOrigin.longitude);
        var waypts = [{
            location: originLatLng
        }];

        angular.forEach($scope.assignedReferrals, function(referral, index) {
            var point = {};
            var latLng = new google.maps.LatLng(referral.coords.latitude, referral.coords.longitude);
            point.location = latLng;
            waypts.push(point);
        });
        var waypointsCount = waypts.length;

        // Split waypoints by 8 to bypass limitation maximum number of waypoints
        // from gmap direction service
        // Ex: [a,b,c,d,e,f,g,h] to [[a,b,c,d],[d,e,f,g],[g,h]]
        //
        var MAX_WAYPOINTS_EXCEEDED = 8;
        for (var i = 0, j = waypointsCount; i < j; i += MAX_WAYPOINTS_EXCEEDED - 1) {
            if (i + 1 == waypointsCount) {
                break;
            }
            var part = waypts.slice(i, i + MAX_WAYPOINTS_EXCEEDED);
            var request = {
                origin: part[0],
                waypoints: part,
                destination: part[part.length - 1],
                optimizeWaypoints: true,
                travelMode: google.maps.TravelMode.DRIVING
            };
            var directionRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: true // Hide direction marker
            });
            $scope.directionRenderers.push(directionRenderer);
            directionRenderer.setMap($scope.map.control.getGMap());
            directionsService.route(request, function (response, status) {
                $scope.loadingOff();
                $scope.$apply();
                if (status == google.maps.DirectionsStatus.OK) {
                    directionRenderer.setDirections(response);
                } else {
                    toastr.error("Could not find route on the map!");
                }
            });
        }
    };

    $scope.saveRoute = function() {
        var data = {};
        data.id = $scope.route.id;
        data.title = $scope.route.title;
        data.status = $scope.route.status;
        data.estimator_id = $scope.route.estimator_id;
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
