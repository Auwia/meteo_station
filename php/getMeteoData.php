<?php
// Your database connection code goes here
$conn = new SQLite3('meteo.db');

// Function to retrieve data for both graphs
function getMeteoData($period, $date) {
    // Initialize data arrays
    $graph1Data = array();
    $graph2Data = array();

    // Retrieve data for graph 1
    $graph1Data = getGraph1Data($period, $date);

    // Retrieve data for graph 2
    $graph2Data = getGraph2Data($period, $date);

    // Combine the data into a single response object
    $response = array(
        'graph1Data' => $graph1Data,
        'graph2Data' => $graph2Data
    );

    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
}

// Function to retrieve data for graph 1
function getGraph1Data($period, $date) {
    // Your logic to fetch data for graph 1
    // ...

    // Return data as an array or object
    return $graph1Data;
}

// Function to retrieve data for graph 2
function getGraph2Data($period, $date) {
    // Your logic to fetch data for graph 2
    // ...

    // Return data as an array or object
    return $graph2Data;
}

// Handle incoming AJAX request
if (isset($_GET['period']) && isset($_GET['date'])) {
    $period = $_GET['period'];
    $date = $_GET['date'];

    // Call the function to retrieve data for both graphs
    getMeteoData($period, $date);
} else {
    // Handle invalid request
    echo 'Invalid request.';
}
?>

