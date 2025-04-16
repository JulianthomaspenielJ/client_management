<?php

// Google Maps API Key
$apiKey = 'AIzaSyBXF0kgN0yVwMQQh1USyf5BQTdYiCE61Co';

// Function to get directions between two locations
function getDirections($origin, $destination, $apiKey) {
    $baseUrl = 'https://maps.googleapis.com/maps/api/directions/json';

    // Construct API request URL
    $url = $baseUrl . '?origin=' . $origin['lat'] . ',' . $origin['lng'] . '&destination=' . $destination['lat'] . ',' . $destination['lng'] . '&key=' . $apiKey;

    // Make the API request
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    // Check if API request was successful
    if ($data['status'] == 'OK') {
        return $data['routes'][0]['legs'][0]['steps'];
    } else {
        return null; // Handle error case
    }
}

// Example locations (replace with your actual locations)
$locations = [
    ['name' => 'Location A', 'lat' => 51.5074, 'lng' => -0.1278],  // London, UK
    ['name' => 'Location B', 'lat' => 48.8566, 'lng' => 2.3522],   // Paris, France
    ['name' => 'Location C', 'lat' => 40.7128, 'lng' => -74.0060], // New York, USA
    ['name' => 'Location D', 'lat' => -33.8688, 'lng' => 151.2093],// Sydney, Australia
];

// Retrieve directions for each pair of locations
$directions = [];
for ($i = 0; $i < count($locations); $i++) {
    for ($j = $i + 1; $j < count($locations); $j++) {
        $origin = $locations[$i];
        $destination = $locations[$j];

        // Get directions
        $steps = getDirections($origin, $destination, $apiKey);

        // Store directions
        if ($steps) {
            $directions[] = [
                'origin' => $origin['name'],
                'destination' => $destination['name'],
                'steps' => $steps
            ];
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($directions);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shortest Directions</title>
    <style>
        #map {
            height: 100vh;
            width: 100%;
        }
    </style>
</head>
<body>
    <div id="map"></div>

    <script>
        // Function to initialize Google Map
        function initMap() {
            // Center map around a location (e.g., London)
            var map = new google.maps.Map(document.getElementById('map'), {
                center: {lat: 51.5074, lng: -0.1278},
                zoom: 3 // Adjust zoom level as needed
            });

            // Call PHP script to get directions
            fetch('getDirections.php')
                .then(response => response.json())
                .then(data => {
                    // Loop through each direction set
                    data.forEach(direction => {
                        // Display directions on map
                        displayDirections(map, direction.steps);
                    });
                })
                .catch(error => console.error('Error fetching directions:', error));
        }

        // Function to display directions on map
        function displayDirections(map, steps) {
            var directionsService = new google.maps.DirectionsService();
            var directionsDisplay = new google.maps.DirectionsRenderer({map: map});

            // Build waypoints from steps
            var waypoints = [];
            steps.forEach(step => {
                waypoints.push({
                    location: {lat: step.start_location.lat, lng: step.start_location.lng},
                    stopover: true
                });
            });

            // Request directions from Google Maps Directions API
            directionsService.route({
                origin: {lat: steps[0].start_location.lat, lng: steps[0].start_location.lng},
                destination: {lat: steps[steps.length - 1].end_location.lat, lng: steps[steps.length - 1].end_location.lng},
                waypoints: waypoints,
                travelMode: 'DRIVING'
            }, function(response, status) {
                if (status === 'OK') {
                    directionsDisplay.setDirections(response);
                } else {
                    console.error('Directions request failed due to ' + status);
                }
            });
        }
    </script>
    <!-- Replace YOUR_GOOGLE_MAPS_API_KEY with your actual Google Maps API key -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBXF0kgN0yVwMQQh1USyf5BQTdYiCE61Co&callback=initMap"></script>
</body>
</html>
