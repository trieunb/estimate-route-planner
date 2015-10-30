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
    var directionsDisplay = new google.maps.DirectionsRenderer({
       suppressMarkers: true // Hide direction marker
    });

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
    $scope.routeStatuses = erpOptions.routeStatuses;

    // Get route data
    crewRouteFactory.get($routeParams.id)
        .success(function(response) {
            $scope.route.id = response.id;
            $scope.route.title = response.title;
            $scope.route.status = response.status;
            // Collect assigned estimates for dragging
            angular.forEach(response.assigned_estimates, function(estimate) {
                estimate.coords = {
                    latitude: estimate.job_lat,
                    longitude: estimate.job_lng
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
                            directionsDisplay.setMap($scope.map.control.getGMap());
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

    /**
     * Repaint direction
     */
    $scope.drawRouteDirection = function() {
        if ($scope.routeOrigin === null) {
            toastr.error('Could not find geo location of company address! The route could not draw!');
            return;
        }
        if ($scope.assignedEstimates.length < 1) {
            // Clear drawed direction
            directionsDisplay.set('directions', null);
            return;
        }
        var originLatLng = new google.maps.LatLng(
            $scope.routeOrigin.latitude, $scope.routeOrigin.longitude);
        var destination = {};
        var waypts = [
            {
                location: originLatLng
            }
        ];

        angular.forEach($scope.assignedEstimates, function(estimate, index) {
            var point = {};
            var latLng = new google.maps.LatLng(estimate.coords.latitude, estimate.coords.longitude);
            point.location = latLng;

            if( (index + 1) === $scope.assignedEstimates.length ) {
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
        data.assigned_estimate_ids = [];

        angular.forEach($scope.assignedEstimates, function(estimate) {
            data.assigned_estimate_ids.push(estimate.id);
        });
        crewRouteFactory.update(data)
            .success(function(response) {
                if (response.success) {
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
