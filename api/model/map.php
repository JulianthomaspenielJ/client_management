<?php

// Replace with your actual API key
$apiKey = 'AIzaSyBXF0kgN0yVwMQQh1USyf5BQTdYiCE61Co';

// Example locations (latitude, longitude)
$locations = [
    ['name' => 'Location A', 'lat' => 40.712776, 'lng' => -74.005974], // New York, NY
    ['name' => 'Location B', 'lat' => 34.052235, 'lng' => -118.243683], // Los Angeles, CA
    ['name' => 'Location C', 'lat' => 41.878113, 'lng' => -87.629799], // Chicago, IL
    ['name' => 'Location D', 'lat' => 37.774929, 'lng' => -122.419418] // San Francisco, CA
];

// Ensure there are at least two locations to calculate a route
if (count($locations) < 2) {
    die("Error: Need at least two locations for routing.");
}

// Prepare the request URL
$baseUrl = 'https://maps.googleapis.com/maps/api/directions/json';
$origin = $locations[0]['lat'] . ',' . $locations[0]['lng']; // Set the first location as the origin
$destination = $locations[0]['lat'] . ',' . $locations[0]['lng']; // Set the first location as the destination
$waypoints = 'optimize:true|' . implode('|', array_map(function($location) {
    return $location['lat'] . ',' . $location['lng'];
}, array_slice($locations, 1))); // Use optimize:true and include all locations except the first one

$params = [
    'origin' => $origin,
    'destination' => $destination,
    'waypoints' => $waypoints,
    'key' => $apiKey
];

$requestUrl = $baseUrl . '?' . http_build_query($params);

// Make the request
$response = file_get_contents($requestUrl);
$data = json_decode($response, true);

// Process the response
if ($data['status'] == 'OK') {
    $route = $data['routes'][0];
    $legs = $route['legs'];

    // Output the optimized route
    echo "Optimized Route:<br>";
    foreach ($legs as $leg) {
        echo $leg['start_address'] . " to " . $leg['end_address'] . ": " . $leg['distance']['text'] . "<br>";
    }
} else {
    echo "Error: " . $data['status'];
}
?>
