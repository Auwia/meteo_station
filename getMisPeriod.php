<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$conn = new SQLite3('meteo.db');
  
$input = $_GET["period"];
$input2 = $_GET["date"];

$format = 'Y-m-d';
$d = DateTime::createFromFormat($format, $input);

if ($input == "Day") {
  $query = "select a.location, a.timestamp as timestamp, round(IFNULL(temperature, 0), 2) as temperature, round(IFNULL(humidity, 0), 2) as humidity from temperatures a inner join humidities b on (a.timestamp = b.timestamp) where strftime('%Y-%m-%d', a.timestamp) = date('" . $input2 . "') and strftime('%Y-%m-%d', b.timestamp) = date('" . $input2 . "') order by a.timestamp asc";
}
  
if ($input == "Last 15") {
  $query = "select * from (SELECT a.timestamp, a.location, IFNULL(round(temperature, 2), 0) AS temperature, IFNULL(round(humidity, 2), 0) AS humidity FROM temperatures a inner join humidities b on (a.timestamp = b.timestamp) order by a.timestamp desc limit 15) order by timestamp";
}  

if ($input == "Month") {
  $query = "select a.location, strftime('%d/%m', a.timestamp) as timestamp, round(AVG(IFNULL(temperature, 0)), 2) as temperature, round(avg(IFNULL(humidity, 0)), 2) as humidity  from temperatures a inner join humidities b on (a.timestamp = b.timestamp) where a.timestamp between date('" . $input2 . "','start of month') and date('" . $input2 . "','start of month','+1 month','-1 day') GROUP BY a.location, strftime('%Y-%m-%d', a.timestamp) order by a.timestamp asc";
}
  
if ($input == "6 Months") {
  $query = "select a.location, strftime('%m/%Y', a.timestamp) as timestamp, round(AVG(IFNULL(temperature, 0)), 2) as temperature, round(avg(IFNULL(humidity, 0)), 2) as humidity  from temperatures a inner join humidities b on (a.timestamp = b.timestamp) where a.timestamp between date('" . $input2 . "','start of month','-6 month') and date('" . $input2 . "','start of month','+1 month','-1 day') GROUP BY a.location, strftime('%Y-%m', a.timestamp) order by a.timestamp asc";
}
  
if ($input == "Year") {
  $query = "select a.location, strftime('%Y/%m', a.timestamp) as timestamp, round(AVG(IFNULL(temperature, 0)), 2) as temperature, round(avg(IFNULL(humidity, 0)), 2) as humidity  from temperatures a inner join humidities b on (a.timestamp = b.timestamp) where a.timestamp between date('" . $input2 . "','start of year') and date('" . $input2 . "','start of month','+1 month','-1 day') GROUP BY a.location, strftime('%Y-%m', a.timestamp) order by a.timestamp asc";
}
  
// echo "<script>console.log('Debug Objects: " . $query . "' );</script>";
$result = $conn->query($query);

$outp = "";
//while($rs = $result->fetch_array(MYSQLI_ASSOC)) {
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
