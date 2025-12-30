<?php
header('Content-Type: application/json');

// Connect to database
$conn = new mysqli("localhost", "root", "", "ddms");

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// Fetch resource counts from resource1 table
$sql = "SELECT resource_type, quantity FROM resource1";
$result = $conn->query($sql);

$resources = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $resources[$row['resource_type']] = $row['quantity'];
    }
}

echo json_encode($resources);
$conn->close();
?>