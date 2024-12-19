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

$temperature_table = "temperatures";
$humidity_table = "humidities";

// Retrieve existing partitions
function getExistingPartitions($conn, $table) {
    $partitions = [];
    $sql = "SELECT PARTITION_NAME 
            FROM INFORMATION_SCHEMA.PARTITIONS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table' 
            AND PARTITION_NAME IS NOT NULL";

    $result = mysqli_query($conn, $sql);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $partition_name = $row['PARTITION_NAME'];
            // Filtra solo partizioni che rispettano il formato pYYYYMMDD
            if (preg_match('/^p\d{8}$/', $partition_name)) {
                $partitions[] = $partition_name;
            }
        }
        error_log("Partizioni filtrate: " . implode(", ", $partitions));
    } else {
        error_log("Errore query partizioni: " . mysqli_error($conn));
    }

    return $partitions;
}

// Filter partitions based on existing ones
function filterPartitions($daterange, $existingPartitions) {
    $filtered = [];
    
    foreach ($daterange as $date) {
        $partitionName = 'p' . $date->format('Ymd');
        if (in_array($partitionName, $existingPartitions)) {
            $filtered[] = $partitionName; // Aggiungi solo se esiste
        }
    }

    return $filtered;
}

// Build the query based on the input
if ($input == "Month") {
    $start_date = date("Y-m-01", strtotime($input2)); // First day of the month
    $end_date = date("Y-m-t 23:59:59", strtotime($input2)); // Last day of the month, including time

    $existingPartitions = getExistingPartitions($conn, $temperature_table);

    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end = $end->modify('+1 day'); // Include the end date for the range

    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end);

    $filteredPartitions = filterPartitions($daterange, $existingPartitions);
    $partitionList = implode(", ", $filteredPartitions);

    $query = "SELECT
     subquery.location,
     subquery.timestamp,
     subquery.temperature,
     subquery2.humidity
 FROM
     (SELECT
          a.location,
          DATE_FORMAT(a.timestamp, '%Y-%m-%d') as timestamp,
          ROUND(AVG(IFNULL(a.temperature, 0)), 2) as temperature
      FROM $temperature_table PARTITION (" . $partitionList . ") a
      WHERE a.timestamp BETWEEN '$start_date' AND '$end_date'
      GROUP BY a.location, DATE_FORMAT(a.timestamp, '%Y-%m-%d')) as subquery
 INNER JOIN
     (SELECT
          b.location,
          DATE_FORMAT(b.timestamp, '%Y-%m-%d') as timestamp,
          ROUND(AVG(IFNULL(b.humidity, 0)), 2) as humidity
      FROM $humidity_table PARTITION (" . $partitionList . ") b
      WHERE b.timestamp BETWEEN '$start_date' AND '$end_date'
      GROUP BY b.location, DATE_FORMAT(b.timestamp, '%Y-%m-%d')) as subquery2
 ON subquery.timestamp = subquery2.timestamp AND subquery.location = subquery2.location
 ORDER BY subquery.timestamp ASC";
}

if ($input == "Year") {
    $year = isset($_GET["year"]) ? $_GET["year"] : date("Y");
    $start_date = "$year-01-01";
    $end_date = "$year-12-31";

    // Ottieni le partizioni esistenti per entrambe le tabelle
    $existingPartitionsTemperatures = getExistingPartitions($conn, $temperature_table);
    $existingPartitionsHumidities = getExistingPartitions($conn, $humidity_table);

    if (empty($existingPartitionsTemperatures) || empty($existingPartitionsHumidities)) {
        die("Errore: Nessuna partizione disponibile per una o più tabelle.");
    }

    // Trova la partizione massima per ciascuna tabella
    $max_partition_temperature = end($existingPartitionsTemperatures);
    $max_partition_humidity = end($existingPartitionsHumidities);

    $max_partition_date_temperature = DateTime::createFromFormat('pYmd', $max_partition_temperature)->format('Y-m-d');
    $max_partition_date_humidity = DateTime::createFromFormat('pYmd', $max_partition_humidity)->format('Y-m-d');

    // Determina la data finale più bassa tra le due tabelle
    $max_partition_date = min($max_partition_date_temperature, $max_partition_date_humidity);
    if ($end_date > $max_partition_date) {
        $end_date = $max_partition_date;
    }

    // Crea l'intervallo di date
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');

    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end);

    // Filtra le partizioni esistenti per ciascuna tabella
    $filteredPartitionsTemperatures = filterPartitions($daterange, $existingPartitionsTemperatures);
    $filteredPartitionsHumidities = filterPartitions($daterange, $existingPartitionsHumidities);

    // Trova le partizioni comuni
    $filteredPartitions = array_intersect($filteredPartitionsTemperatures, $filteredPartitionsHumidities);

    if (empty($filteredPartitions)) {
        die("Errore: Nessuna partizione valida disponibile per l'intervallo di date.");
    }

    $partitionList = implode(", ", $filteredPartitions);

    // Costruisci la query SQL
    $query = "SELECT
                 a.location,
                 DATE_FORMAT(a.timestamp, '%Y-%m') AS timestamp,
                 ROUND(AVG(IFNULL(a.temperature, 0)), 2) AS temperature,
                 ROUND(AVG(IFNULL(b.humidity, 0)), 2) AS humidity
              FROM $temperature_table PARTITION ($partitionList) a
              INNER JOIN $humidity_table PARTITION ($partitionList) b 
              ON a.timestamp = b.timestamp
              GROUP BY MONTH(a.timestamp)
              ORDER BY a.timestamp ASC";

    error_log("Query generata: " . $query);
}

if ($input == "Day") {
    $partition_name = "p" . trim(date("Ymd", strtotime($input2)));
    $query = "SELECT a.location, a.timestamp AS timestamp, ROUND(IFNULL(a.temperature, 0), 2) AS temperature, ROUND(IFNULL(b.humidity, 0), 2) AS humidity
              FROM $temperature_table PARTITION ($partition_name) a
              LEFT JOIN $humidity_table PARTITION ($partition_name) b ON a.timestamp = b.timestamp
              WHERE DATE(a.timestamp) = date('$input2')
              ORDER BY a.timestamp";
}
if ($input == "Last 35") {
    $partition_name = "p" . date("Ymd");
    $query = "SELECT * FROM (SELECT a.location, a.timestamp AS timestamp, ROUND(IFNULL(a.temperature, 0), 2) AS temperature, ROUND(IFNULL(b.humidity, 0), 2) AS humidity FROM $temperature_table PARTITION ($partition_name) a LEFT JOIN $humidity_table PARTITION ($partition_name) b ON a.timestamp = b.timestamp where DATE(a.timestamp) = DATE(curdate()) ORDER BY a.timestamp DESC LIMIT 35) as subquery ORDER BY timestamp";
}

if ($input == "6 Months") {
    // Calculate start date for 6 months ago
    $start_date = date("Y-m-d", strtotime("-6 months", strtotime($input2)));

    // Retrieve existing partitions for the temperature table
    $existingPartitionsTemp = getExistingPartitions($conn, $temperature_table);
    $existingPartitionsHum = getExistingPartitions($conn, $humidity_table);

    // Set date range for partition filter
    $start = new DateTime($start_date);
    $end = new DateTime($input2);
    $end = $end->modify('+1 day'); // Include the end date

    $interval = new DateInterval('P1D');
    $daterange = new DatePeriod($start, $interval, $end);

    // Filter partitions based on existing ones for both temperature and humidity tables
    $filteredPartitionsTemp = filterPartitions($daterange, $existingPartitionsTemp);
    $filteredPartitionsHum = filterPartitions($daterange, $existingPartitionsHum);

    // Convert partition names to string for SQL query
    $partitionListTemp = implode(", ", $filteredPartitionsTemp);
    $partitionListHum = implode(", ", $filteredPartitionsHum);

    $query = "SELECT 
                 a.location, 
                 DATE_FORMAT(a.timestamp, '%Y-%m') AS timestamp, 
                 ROUND(AVG(IFNULL(a.temperature, 0)), 2) AS temperature, 
                 ROUND(AVG(IFNULL(b.humidity, 0)), 2) AS humidity 
              FROM $temperature_table PARTITION (" . $partitionListTemp . ") a
              LEFT JOIN $humidity_table PARTITION (" . $partitionListHum . ") b ON a.timestamp = b.timestamp
              WHERE a.timestamp BETWEEN '$start_date' AND '$input2'
              GROUP BY YEAR(a.timestamp), MONTH(a.timestamp)
              ORDER BY YEAR(a.timestamp) ASC, MONTH(a.timestamp) ASC";
}

// Execute and output the query
if (!isset($query)) {
    error_log("Invalid input: $input");
}

// error_log("Running SQL Query: $query");

$result = $conn->query($query);

if ($result === false) {
    error_log("Query execution finished for input: $input, date: $input2");
    die("Error in SQL query: " . $conn->error);
}

$outp = "";

while ($rs = $result->fetch_assoc()) {
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
