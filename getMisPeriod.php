<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Database connection
$conn = new SQLite3('meteo.db');

$input = $_GET["period"];
$input2 = $_GET["date"];

$format = 'Y-m-d';
$d = DateTime::createFromFormat($format, $input2);

if ($input == "Day") {
    $query = "SELECT a.location, a.timestamp AS timestamp, ROUND(IFNULL(a.temperature, 0), 2) AS temperature, ROUND(IFNULL(b.humidity, 0), 2) AS humidity FROM temperatures a
        LEFT JOIN humidities b ON a.timestamp = b.timestamp
        WHERE strftime('%Y-%m-%d', a.timestamp) = date('" . $input2 . "') 
        ORDER BY a.timestamp ASC";
}
  
if ($input == "Last 15") {
  $query = "SELECT * FROM (SELECT a.timestamp, a.location, IFNULL(ROUND(temperature, 2), 0) AS temperature, IFNULL(ROUND(humidity, 2), 0) AS humidity FROM temperatures a INNER JOIN humidities b ON (a.timestamp = b.timestamp) ORDER BY a.timestamp DESC LIMIT 15) ORDER BY timestamp";
}  

if ($input == "Month") {
  $query = "SELECT a.location, strftime('%d/%m', a.timestamp) as timestamp, ROUND(AVG(IFNULL(temperature, 0)), 2) as temperature, ROUND(AVG(IFNULL(humidity, 0)), 2) as humidity FROM temperatures_" . $d->format('Y_m') . " a INNER JOIN humidities_" . $d->format('Y_m') . " b ON (a.timestamp = b.timestamp) WHERE a.timestamp BETWEEN date('" . $input2 . "','start of month') AND date('" . $input2 . "','start of month','+1 month','-1 day') GROUP BY a.location, strftime('%Y-%m-%d', a.timestamp) ORDER BY a.timestamp ASC";
}
  
if ($input == "6 Months") {
  $query = "SELECT a.location, strftime('%m/%Y', a.timestamp) as timestamp, ROUND(AVG(IFNULL(temperature, 0)), 2) as temperature, ROUND(AVG(IFNULL(humidity, 0)), 2) as humidity FROM temperatures_" . $d->format('Y_m') . " a INNER JOIN humidities_" . $d->format('Y_m') . " b ON (a.timestamp = b.timestamp) WHERE a.timestamp BETWEEN date('" . $input2 . "','start of month','-6 month') AND date('" . $input2 . "','start of month','+1 month','-1 day') GROUP BY a.location, strftime('%Y-%m', a.timestamp) ORDER BY a.timestamp ASC";
}
  
if ($input == "Year") {
  $query = "SELECT a.location, strftime('%Y/%m', a.timestamp) as timestamp, ROUND(AVG(IFNULL(temperature, 0)), 2) as temperature, ROUND(AVG(IFNULL(humidity, 0)), 2) as humidity FROM temperatures_" . $d->format('Y') . " a INNER JOIN humidities_" . $d->format('Y') . " b ON (a.timestamp = b.timestamp) WHERE a.timestamp BETWEEN date('" . $input2 . "','start of year') AND date('" . $input2 . "','start of month','+1 month','-1 day') GROUP BY a.location, strftime('%Y-%m', a.timestamp) ORDER BY a.timestamp ASC";
}

$result = $conn->query($query);

$outp = "";

while ($rs = $result->fetchArray()) {
    if ($outp != "") {$outp .= ",";}
    $outp .= '{"timestamp":"'  . $rs["timestamp"] . '",';
    $outp .= '"location":"'   . $rs["location"]        . '",';
    $outp .= '"temperature":'. $rs["temperature"]     . ','; 
    $outp .= '"humidity":'. $rs["humidity"]     . '}'; 
}

$outp ='['.$outp.']';
$conn->close();

echo($outp);
?>
