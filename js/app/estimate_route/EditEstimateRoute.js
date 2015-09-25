angular.module('Erp')
   .controller(
       'EditEstimateRouteCtrl',
       [
           '$scope',
           '$rootScope',
           'estimateFactory',
           'estimateRouteFactory',
           '$location',
           '$routeParams',
           'uiGmapGoogleMapApi',
           'uiGmapIsReady',
           '$filter',
           EditEstimateRouteCtrl
        ]
    );

function EditEstimateRouteCtrl(
    $scope,
    $rootScope,
    estimateFactory,
    estimateRouteFactory,
    $location,
    $routeParams,
    uiGmapGoogleMapApi,
    uiGmapIsReady,
    $filter) {

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

           // Get route data
            estimateRouteFactory.get($routeParams.id)
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
                   // Setting gmap
                   uiGmapGoogleMapApi.then(function(maps) {
                       var center  = {};
                       if ($scope.assignedEstimates.length) {
                           center = {
                               latitude: $scope.assignedEstimates[0].coords.latitude,
                               longitude: $scope.assignedEstimates[0].coords.longitude
                           };
                       } else if($scope.pendingEstimates.length) {
                           center = {
                               latitude: $scope.pendingEstimates[0].coords.latitude,
                               longitude: $scope.pendingEstimates[0].coords.longitude
                           };
                       }

                       $scope.map.options = {
                           center: center,
                           zoom: 14,
                           MapTypeId: maps.MapTypeId.HYBRID
                       };
                       return maps;
                   });
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
               $scope.drawAssignedEstimatesDirection(); // Render direction after loaded
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
           var latLng = new google.maps.LatLng(estimate.coords.latitude, estimate.coords.longitude);
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
           toastr.error("A route could not be saved without any assigned estimates!");
       } else {
           var data = {};
           data.id = $scope.route.id;
           data.title = $scope.route.title;
           data.status = $scope.route.status;
           data.assigned_estimate_ids = [];

           angular.forEach($scope.assignedEstimates, function(estimate) {
               data.assigned_estimate_ids.push(estimate.id);
           });
           estimateRouteFactory.update(data)
               .success(function(response) {
                   if (response.success) {
                       toastr.success(response.message);
                   } else {
                       var msg = response.message || 'An error occurred while saving estimate';
                       toastr.error(msg);
                   }
               });
       }
   };

   $scope.printRoute = function() {
        if ($scope.assignedEstimates.length < 2) {
            toastr.error("Print route require at least two assigned estimates!");
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
