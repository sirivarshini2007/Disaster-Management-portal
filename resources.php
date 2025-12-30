<?php
include 'config.php';

$sql = "SELECT * FROM resources WHERE quantity > 0";
$result = $conn->query($sql);

$resources = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($resources);

$conn->close();
?>