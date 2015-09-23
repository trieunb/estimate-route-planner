 angular.module('Erp')
    .controller('AddEstimateRouteCtrl', [
        '$scope',
        '$rootScope',
        'estimateFactory',
        'estimateRouteFactory',
        '$location',
        'uiGmapGoogleMapApi',
        'uiGmapIsReady',
        '$filter',
        AddEstimateRouteCtrl
    ]);

function AddEstimateRouteCtrl(
    $scope,
    $rootScope,
    estimateFactory,
    estimateRouteFactory,
    $location,
    uiGmapGoogleMapApi,
    uiGmapIsReady,
    $filter) {

    var orderBy = $filter('orderBy');
    var directionsService = new google.maps.DirectionsService();
    var directionsDisplay = new google.maps.DirectionsRenderer({
        suppressMarkers: true // Hide direction marker
    });

    $scope.setPageTitle('Estimate route planner');
    $scope.route = {}; // Form data
    $scope.pendingEstimates = [];
    $scope.assignedEstimates = [];
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
            label: 'Total',
            value: 'total'
        },
        {
            label: 'Due date',
            value: 'due_date'
        }
    ];
    // Loading non-assigned estimates
    estimateFactory.listUnassigned()
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

            // Setting gmap
            uiGmapGoogleMapApi.then(function(maps) {
                var bounds = new google.maps.LatLngBounds();
                angular.forEach($scope.pendingEstimates, function(estimate) {
                    bounds.extend(new google.maps.LatLng(estimate.coords.latitude, estimate.coords.longitude));
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
            estimateRouteFactory.recent()
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

    $scope.onPendingMoved = function(estimate, estimateIndex) {
        $scope.pendingEstimates.splice(estimateIndex, 1);
        $scope.drawAssignedEstimatesDirection();
    };

    $scope.onAssignedMoved = function(estimate, estimateIndex) {
        $scope.assignedEstimates.splice(estimateIndex, 1);
        $scope.drawAssignedEstimatesDirection();
    };

    $scope.sortAssignedQueue = function() {
        $scope.assignedEstimates = orderBy(
            $scope.assignedEstimates,
            $scope.assigned_queue_sort_by,
            false
        );
        $scope.drawAssignedEstimatesDirection();
    };

    $scope.sortPendingQueue = function() {
        $scope.pendingEstimates = orderBy(
            $scope.pendingEstimates,
            $scope.pending_queue_sort_by,
            false
        );
    };

    /**
     * Repaint direction
     */
    $scope.drawAssignedEstimatesDirection = function() {
        if ($scope.assignedEstimates.length < 2) {
            // Clear drawed direction
            directionsDisplay.set('directions', null);
            return;
        }
        var origin = {}; // start
        var destination = {}; // end
        var waypts = [];

        angular.forEach($scope.assignedEstimates, function(estimate, index) {
            var point = {};
            var latLng = new google.maps.LatLng(estimate.coords.latitude, estimate.coords.longitude);;
            point.location = latLng;

            if (index === 0) {
                origin = latLng;
            }

            if( (index + 1) === $scope.assignedEstimates.length ) {
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
        if ($scope.assignedEstimates.length == 0) {
            toastr['error']("A route could not be saved without any assigned estimates!");
        } else {
            var data = {};
            data.title = $scope.route.title;
            data.assigned_estimate_ids = [];
            angular.forEach($scope.assignedEstimates, function(estimate) {
                data.assigned_estimate_ids.push(estimate.id);
            });
            estimateRouteFactory.save(data)
                .success(function(response) {
                    if (response.success) {
                        toastr['success'](response.message);
                        $location.path('/edit-estimate-route/' + response.data.id);
                    } else {
                        var msg = response.message || 'An error occurred while saving estimate';
                        toastr['error'](msg);
                    }
                });
        }
    }

   $scope.printRoute = function() {
        if ($scope.assignedEstimates.length < 2) {
            toastr['error']("Print route require at least two assigned estimates!");
        } else {
            var url = 'https://www.google.com/maps/dir/am=t' + getGmapURL();
            window.open(url, '_blank');
        }
    };

    var getGmapURL = function() {
        var latlng = [] ;
        angular.forEach($scope.assignedEstimates, function(estimate) {
            var tmp = [];
            estimate.coords = {
                latitude: estimate.job_lat,
                longitude: estimate.job_lng
            };
            tmp.push(estimate.coords.latitude);
            tmp.push(estimate.coords.longitude);
            latlng.push(tmp);
        });

        var result = '';
        angular.forEach(latlng, function(value) {
            result += '/' + value[0] + ',' + value[1];
        });
        return result;
    };
}
