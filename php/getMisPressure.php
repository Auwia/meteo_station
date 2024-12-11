<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection
$servername = "localhost";
$username = "pi";
$password = "pi_db_meteo";
$dbname = "meteo";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
  
$input = $_GET["period"];
$input2 = $_GET["date"];

$format = 'Y-m-d';
$d = DateTime::createFromFormat($format, $input2);

// Validate and format the date input
if (!$d) {
    die("Invalid date format. Please use 'Y-m-d' format for the date input.");
}

if ($input == "Day") {
  $partition_name = "p" . trim(date("Ymd", strtotime($input2)));
  $query = "SELECT location, timestamp as timestamp, ROUND(IFNULL(pressure, 0), 2) as pressure, ROUND(IFNULL(temperature, 0), 2) as temperature, ROUND(IFNULL(altitude, 0), 2) as altitude FROM pressures PARTITION ($partition_name) WHERE DATE(timestamp) = DATE('" . $input2 . "') ORDER BY timestamp";
}

if ($input == "Last 35") {
  $partition_name = "p" . date("Ymd");
  $query = "SELECT * FROM (SELECT timestamp, location, ROUND(IFNULL(pressure, 0), 2) as pressure, ROUND(IFNULL(temperature, 0), 2) as temperature, ROUND(IFNULL(altitude, 0), 2) as altitude FROM pressures PARTITION ($partition_name) ORDER BY timestamp DESC LIMIT 35) as subquery ORDER BY timestamp";
}

if ($input == "Month") {
    // Calculate the first and last day of the month from $input2
    $start_date = date("Y-m-01", strtotime($input2)); // First day of the month
    $end_date = date("Y-m-t", strtotime($input2));    // Last day of the month

    // Create a DatePeriod to iterate over each day of the month
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end = $end->modify('+1 day'); // Include the end date

    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end);

    // Generate the partition names
    $partition_names = [];
    foreach($daterange as $date) {
        $partition_names[] = "p" . $date->format("Ymd");
    }

    // Join the partition names into a string for the SQL query
    $partition_list = implode(", ", $partition_names);

    // Construct the SQL query
    $query = "SELECT a.location, 
                     DATE_FORMAT(a.timestamp, '%d/%m/%Y') as timestamp, 
		     ROUND(IFNULL(pressure, 0), 2) as pressure,
                     ROUND(AVG(IFNULL(a.temperature, 0)), 2) as temperature, 
                     ROUND(AVG(IFNULL(a.altitude, 0)), 2) as altitude 
              FROM pressures PARTITION ($partition_list) a 
	      WHERE a.timestamp BETWEEN '" . $start_date . "' AND '" . $end_date . " 23:59:59'
              GROUP BY a.location, DATE_FORMAT(a.timestamp, '%Y-%m-%d')
              ORDER BY a.timestamp ASC";
}

if ($input == "6 Months") {
  $query = "SELECT a.location, DATE_FORMAT(a.timestamp, '%m/%Y') as timestamp, ROUND(AVG(IFNULL(a.temperature, 0)), 2) as temperature, ROUND(AVG(IFNULL(a.altitude, 0)), 2) as altitude FROM pressures a WHERE a.timestamp >= DATE_SUB(DATE_FORMAT('" . $input2 . "','%Y-%m-01'), INTERVAL 6 MONTH) GROUP BY a.location, DATE_FORMAT(a.timestamp, '%Y-%m') ORDER BY a.timestamp ASC";
}

if ($input == "Year") {
  $query = "SELECT a.location, DATE_FORMAT(a.timestamp, '%Y/%m') as timestamp, ROUND(AVG(IFNULL(a.temperature, 0)), 2) as temperature, ROUND(AVG(IFNULL(a.altitude, 0)), 2) as altitude FROM pressures a WHERE YEAR(a.timestamp) = YEAR('" . $input2 . "') GROUP BY a.location, DATE_FORMAT(a.timestamp, '%Y-%m') ORDER BY a.timestamp ASC";
}

// echo "<script>console.log('Debug Objects: " . $query . "' );</script>";
$result = $conn->query($query);

if ($result === false) {
    die("Error in SQL query: " . $conn->error);
}

// error_log("Query DEBUG: $query");

$outp = "";
while($rs = $result->fetch_assoc()) {
    if ($outp != "") {$outp .= ",";}
    $outp .= '{"timestamp":"'  . $rs["timestamp"] . '",';
    $outp .= '"location":"'   . $rs["location"]        . '",';
    $outp .= '"pressure":'. $rs["pressure"]     . ',';
    $outp .= '"temperature":'. $rs["temperature"]     . ','; 
    $outp .= '"altitude":'. $rs["altitude"]     . '}'; 
}
$outp ='['.$outp.']';
$conn->close();

echo $outp;
?>
