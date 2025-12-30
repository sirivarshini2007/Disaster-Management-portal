<?php
header('Content-Type: application/json');

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ddms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "SELECT id, name, current_capacity, max_capacity, location FROM shelters";
$result = $conn->query($sql);

$shelters = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $shelters[] = $row;
    }
}

echo json_encode($shelters);
$conn->close();
?>