angular.module('Erp')
   .controller(
       'EditCrewRouteCtrl',
       [
           '$scope',
           '$rootScope',
           'estimateFactory',
           'crewRouteFactory',
           '$location',
           '$routeParams',
           'uiGmapGoogleMapApi',
           'uiGmapIsReady',
           '$filter',
           'erpOptions',
           'sharedData',
           'erpGeoLocation',
           EditCrewRouteCtrl
        ]
    );

function EditCrewRouteCtrl(
    $scope,
    $rootScope,
    estimateFactory,
    crewRouteFactory,
    $location,
    $routeParams,
    uiGmapGoogleMapApi,
    uiGmapIsReady,
    $filter,
    erpOptions,
    sharedData,
    erpGeoLocation) {

    var orderBy = $filter('orderBy');
    var directionsService = new google.maps.DirectionsService();

    $scope.setPageTitle('Crew Route planner');
    $scope.route = {}; // Form data
    $scope.pendingEstimates = [];
    $scope.assignedEstimates = [];
    $scope.currentAssignedEstimates = [];
    $scope.pendingMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/blue-marker.png' };
    $scope.startMarkerIcon = {url: $rootScope.baseERPPluginUrl + 'images/start-marker.png' };
    $scope.map = {control: {}};  // Hold map instance
    $scope.map.options = {};
    $scope.assigned_queue_sort_by = '';
    $scope.pending_queue_sort_by = '';
    $scope.routeOrigin = null;
    $scope.routeOriginAddress = sharedData.companyInfo.full_address;
    $scope.sortOptions = erpOptions.sortCrewRoute;
    $scope.directionRenderers = [];

    $scope.viewOptions = {
        compact: true
    };

    // Get route data
    crewRouteFactory.get($routeParams.id)
        .success(function(response) {
            $scope.route.id = response.id;
            $scope.route.title = response.title;
            // Collect assigned estimates for dragging
            angular.forEach(response.assigned_estimates, function(estimate) {
                estimate.coords = {
                    latitude: estimate.job_lat,
                    longitude: estimate.job_lng
                };
                estimate.markerEvents = {
                    click: function() {
                        $scope.clearHighlights();
                        estimate.highlight = true;
                    }
                };
                estimate.total = parseFloat(estimate.total);
                $scope.assignedEstimates.push(estimate);
                $scope.currentAssignedEstimates.push(estimate);
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
                            $scope.drawRouteDirection();
                        });
                    },
                    function() {
                        toastr.error('Could not find geo location of company address! The route could not draw!');
                    }
                );
        });

    // Load assignable estimates
    estimateFactory.listAssignable()
       .success(function(response) {
           // Collect pending estimates for dragging
           angular.forEach(response, function(estimate) {
               if(estimate.job_lat && estimate.job_lng) {
                    estimate.coords = {
                        latitude: estimate.job_lat,
                        longitude: estimate.job_lng
                    };
                    estimate.markerEvents = {
                        click: function() {
                            $scope.clearHighlights();
                            estimate.highlight = true;
                        }
                    };
                    estimate.total = parseFloat(estimate.total);
                    $scope.pendingEstimates.push(estimate);
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
        if ($scope.assignedEstimates.length > 1) {
            $scope.assignedEstimates = orderBy(
                $scope.assignedEstimates,
                $scope.assigned_queue_sort_by,
                false
            );
            $scope.drawRouteDirection();
        }
    };

    $scope.sortPendingQueue = function() {
        if ($scope.pendingEstimates.length > 1) {
            $scope.pendingEstimates = orderBy(
                $scope.pendingEstimates,
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
        if ($scope.assignedEstimates.length < 1) {
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

        angular.forEach($scope.assignedEstimates, function(estimate, index) {
            var point = {};
            var latLng = new google.maps.LatLng(
                estimate.coords.latitude, estimate.coords.longitude);
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
                suppressMarkers: true,
                polylineOptions: erpOptions.mapPolylineOptions
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

    $scope.moveItemToPendingQueue = function(estimate) {
        $scope.pending_queue_sort_by = '';
        $scope.assigned_queue_sort_by = '';
        for (var i = 0; i < $scope.assignedEstimates.length; i++) {
            if ($scope.assignedEstimates[i].id == estimate.id) {
                $scope.assignedEstimates.splice(i, 1);
            }
        }
        $scope.pendingEstimates.push(estimate);
        $scope.drawRouteDirection();
    };

    $scope.moveItemToAssignedQueue = function(estimate) {
        $scope.pending_queue_sort_by = '';
        $scope.assigned_queue_sort_by = '';
        for (var i = 0; i < $scope.pendingEstimates.length; i++) {
            if ($scope.pendingEstimates[i].id == estimate.id) {
                $scope.pendingEstimates.splice(i, 1);
            }
        }
        $scope.assignedEstimates.push(estimate);
        $scope.drawRouteDirection();
    };

    $scope.openMarker = function(estimate) {
        estimate.show_infor_window = true;
        $scope.map.control.getGMap().setCenter(
            new google.maps.LatLng(
                estimate.coords.latitude,
                estimate.coords.longitude
            )
        );
    };

    $scope.clearHighlights = function() {
        for (var i = 0; i < $scope.pendingEstimates.length; i++) {
            $scope.pendingEstimates[i].highlight = false;
        }
        for (var j = 0; j < $scope.assignedEstimates.length; j++) {
            $scope.assignedEstimates[j].highlight = false;
        }
    };

    $scope.saveRoute = function() {
        var data = {};
        data.id = $scope.route.id;
        data.title = $scope.route.title;
        data.assigned_estimate_ids = [];

        angular.forEach($scope.assignedEstimates, function(estimate) {
            data.assigned_estimate_ids.push(estimate.id);
        });
        crewRouteFactory.update(data)
            .success(function(response) {
                if (response.success) {
                    angular.forEach($scope.assignedEstimates, function(estimate) {
                        if (estimate.status === 'Accepted') {
                            estimate.status = 'Routed';
                        }
                    });
                    angular.forEach($scope.pendingEstimates, function(estimate) {
                        estimate.status = 'Accepted';
                    });
                    toastr.success(response.message);
                    $scope.currentAssignedEstimates = [];
                    angular.copy($scope.assignedEstimates, $scope.currentAssignedEstimates);
                } else {
                    var msg = response.message || 'An error occurred while saving estimate';
                    toastr.error(msg);
                }
            });
    };

    $scope.printRoute = function() {
        if ($scope.routeOrigin === null) {
           toastr.error('Could not find geo location of company address! The route could not draw!');
           return;
        }
        if ($scope.assignedEstimates.length < 1) {
            toastr.error("Print route require at least 1 estimate!");
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
        angular.forEach($scope.assignedEstimates, function(estimate) {
            latlngs.push(estimate.coords);
        });

        var result = '';
        angular.forEach(latlngs, function(point) {
            result += '/' + point.latitude + ',' + point.longitude;
        });
        return result;
    };
}
