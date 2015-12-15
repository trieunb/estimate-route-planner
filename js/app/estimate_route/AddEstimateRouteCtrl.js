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

    $scope.setPageTitle('New Estimate Route');
    $scope.route = { // Form data
        status: 'Pending'
    };
    $scope.pendingReferrals = [];
    $scope.assignedReferrals = [];
    $scope.recentRoutes = [];

    // TODO: DRY-ing up
    $scope.pendingMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/blue-marker.png'};
    $scope.startMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/grey-marker.png'};
    $scope.firstMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/green-marker.png'};
    $scope.middleMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/purple-marker.png'};
    $scope.lastMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/red-marker.png'};

    $scope.map = { control: {} };  // Hold map instance
    $scope.map.options = {};
    $scope.assigned_queue_sort_by = '';
    $scope.pending_queue_sort_by = '';
    $scope.routeOrigin = null;
    $scope.routeOriginAddress = sharedData.companyInfo.full_address;
    $scope.sortOptions = erpOptions.sortEstimateRoute;
    $scope.directionRenderers = [];

    $scope.viewOptions = {
        compact: true
    };
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
                referral.markerEvents = {
                    click: function() {
                        $scope.clearHighlights();
                        referral.highlight = true;
                    }
                };
                referral.markerOptions = {
                    icon: $scope.pendingMarkerIcon
                };
                $scope.pendingReferrals.push(referral);
            });
        });
    // Load recent saved routes
    estimateRouteFactory.recent()
        .success(function(response) {
            $scope.recentRoutes = response;
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

    var refreshMarkers = function() {
        angular.forEach($scope.pendingReferrals, function(referral, index) {
            var markerOptions = {
                icon: $scope.pendingMarkerIcon
            };
            referral.markerOptions = markerOptions;
        });

        angular.forEach($scope.assignedReferrals, function(referral, index) {
            var markerOptions = {
                label: {
                    text: erpOptions.map.markerLabels[index],
                    color: '#FFF'
                }
            };
            if (index === 0) {
                markerOptions.label.backgroundColor = erpOptions.map.firstPointColor;
                markerOptions.icon = $scope.firstMarkerIcon;
            } else {
                if (index + 1 === $scope.assignedReferrals.length) {
                    markerOptions.label.backgroundColor = erpOptions.map.lastPointColor;
                    markerOptions.icon = $scope.lastMarkerIcon;
                } else {
                    markerOptions.label.backgroundColor = erpOptions.map.middlePointColor;
                    markerOptions.icon = $scope.middleMarkerIcon;
                }
            }
            referral.markerOptions = markerOptions;
        });
    };

    /**
     * Repaint direction
     */
    $scope.drawRouteDirection = function() {
        refreshMarkers();
        if ($scope.routeOrigin === null) {
            toastr.error('Could not find geo location of company address! The route could not draw!');
            return;
        }
        clearDirections();
        if ($scope.assignedReferrals.length < 1) {
            return;
        }
        $scope.loadingOn();

        var originLatLng = new google.maps.LatLng(
            $scope.routeOrigin.latitude, $scope.routeOrigin.longitude);
        var waypts = [{
            location: originLatLng
        }];

        var startColor = '#77DB2B';
        var stopColor = '#FC0A16';
        var markerTexts = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        angular.forEach($scope.assignedReferrals, function(referral, index) {
            var point = {};
            var latLng = new google.maps.LatLng(
                referral.coords.latitude, referral.coords.longitude);
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
            directionsService.route(request, function (response, status) {
                var directionRenderer = new google.maps.DirectionsRenderer({
                    suppressMarkers: true,
                    polylineOptions: erpOptions.map.polylineOptions
                });
                $scope.directionRenderers.push(directionRenderer);
                directionRenderer.setMap($scope.map.control.getGMap());
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

    $scope.moveItemToPendingQueue = function(referral) {
        $scope.pending_queue_sort_by = '';
        $scope.assigned_queue_sort_by = '';
        for (var i = 0; i < $scope.assignedReferrals.length; i++) {
            if ($scope.assignedReferrals[i].id == referral.id) {
                $scope.assignedReferrals.splice(i, 1);
            }
        }
        $scope.pendingReferrals.push(referral);
        $scope.drawRouteDirection();
    };

    $scope.moveItemToAssignedQueue = function(referral) {
        $scope.pending_queue_sort_by = '';
        $scope.assigned_queue_sort_by = '';
        for (var i = 0; i < $scope.pendingReferrals.length; i++) {
            if ($scope.pendingReferrals[i].id == referral.id) {
                $scope.pendingReferrals.splice(i, 1);
            }
        }
        $scope.assignedReferrals.push(referral);
        $scope.drawRouteDirection();
    };

    $scope.openMarker = function(referral) {
        referral.show_infor_window = true;
        $scope.map.control.getGMap().setCenter(
            new google.maps.LatLng(
                referral.coords.latitude,
                referral.coords.longitude
            )
        );
    };

    $scope.clearHighlights = function() {
        for (var i = 0; i < $scope.pendingReferrals.length; i++) {
            $scope.pendingReferrals[i].highlight = false;
        }
        for (var j = 0; j < $scope.assignedReferrals.length; j++) {
            $scope.assignedReferrals[j].highlight = false;
        }
    };

    $scope.saveRoute = function() {
        var data = {};
        data.title = $scope.route.title;
        data.assigned_referral_ids = [];
        data.estimator_id = $scope.route.estimator_id;
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
