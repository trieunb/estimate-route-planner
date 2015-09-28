<html>
<head>
    <title><?php echo $referral_title; ?></title>
    <script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
    <style type="text/css">

        @media all {
            body { 
                font-size: 10pt; 
            }
            div.head {
                width: 1200px;
                margin: 0 auto;
            }
            div#map {
                height: 300px;
                border: 2px solid #C0C0C0;
                width: 1200px;
                margin: 0 auto;
            }
            .adp-legal, .gmnoprint, .gm-style-cc, .gmnoprint gm-style-cc {
                display: none;
            }
            div#directions {
                width: 1200px;
                margin: 0 auto;
            }  
        }
        
    </style>
</head>
<body>
    <?php if (count($points) > 1) { ?>
        <div class="head"><h1><?php echo $referral_title; ?></h1></div>
        <div id="map" class="row"></div>
        <div id="directions"></div>
    <?php } else { ?>
        <h1 class="text-center">Not Found Referral Route</h1>
    <?php } ?>
    <script type="text/javascript">
        var points = <?php echo json_encode($points) ?>;
        var start = <?php echo json_encode($start) ?> ;
        var end = <?php echo json_encode($end) ?>;
        var directionsDisplay = new google.maps.DirectionsRenderer({
            suppressMarkers: false // Hide direction marker
        });
        var directionsService = new google.maps.DirectionsService;
        var startLL = new google.maps.LatLng(start.lat, start.lng);
        var endLL = new google.maps.LatLng(end.lat, end.lng);
        var waypts = [];
        for(var i = 0 ; i < points.length ; i++) {
            var latLng = new google.maps.LatLng(points[i].lat, points[i].lng);
            waypts.push({
                location: latLng
            });
        }
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 15,
        });

        directionsDisplay.setMap(map);
        directionsDisplay.setPanel(document.getElementById('directions')); 
        
        var request = {
            origin : startLL,
            waypoints: waypts,
            destination: endLL,
            optimizeWaypoints: true,
            travelMode: google.maps.TravelMode.DRIVING
        };

        directionsService.route(request, function(response, status) {
            if (status == google.maps.DirectionsStatus.OK) {
                directionsDisplay.setDirections(response);
                window.onload = function() { setTimeout(function(){ self.print() }, 300); }
            } else {
                window.alert('Directions request failed due to ' + status);
            }
        });           
    </script>
</body>
</html>
